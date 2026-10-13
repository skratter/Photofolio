// Akkordfinder: Tonleitern, Akkorde und die Akkord-Erkennung kommen fertig
// berechnet vom Server (BuildChordFinderDataAction / IdentifyChordAction),
// Halbtöne gezählt ab dem C der ersten Oktave. Hier passiert nur Auswahl,
// Hervorhebung, Ton, Akkordfolgen und Hörtraining.

const SILENCE = 0.0001;

const startAndStop = (oscillators, start, end) => {
    oscillators.forEach((node) => {
        node.start(start);
        node.stop(end);
    });
};

const createOscillator = (context, type, frequency, detuneCents = 0) => {
    const node = context.createOscillator();

    node.type = type;
    node.frequency.value = frequency;
    node.detune.value = detuneCents;

    return node;
};

const createFilter = (context, type, frequency, output, q = 0.7) => {
    const filter = context.createBiquadFilter();

    filter.type = type;
    filter.frequency.value = frequency;
    filter.Q.value = q;
    filter.connect(output);

    return filter;
};

// Gemeinsame Hüllkurve für gehaltene Klänge: einblenden, halten, ausklingen.
const heldVoice = (context, output, start, duration, peak, attack, release, createOscillators) => {
    const gain = context.createGain();

    gain.gain.setValueAtTime(SILENCE, start);
    gain.gain.exponentialRampToValueAtTime(peak, start + attack);
    gain.gain.setValueAtTime(peak, Math.max(start + attack, start + duration));
    gain.gain.exponentialRampToValueAtTime(SILENCE, start + duration + release);
    gain.connect(output);

    startAndStop(createOscillators(gain), start, start + duration + release + 0.05);
};

// Die Klänge werden live mit Web Audio synthetisiert (keine Sample-Dateien).
// Jede Stimme bekommt Kontext, Ausgang, Frequenz, Startzeit, Haltedauer und
// Lautstärke. Schlagende Klänge (Klavier, E-Piano) klingen von selbst ab,
// gehaltene (Synth, Orgel, Streicher) bleiben bis zur Haltedauer stehen.
const voices = {
    // Obertöne mit unterschiedlicher Abklingzeit: [Faktor, Anteil, Sekunden].
    piano(context, output, frequency, start, duration, peak) {
        const partials = [[1, 1, 2.6], [2, 0.5, 1.7], [3, 0.28, 1.1], [4, 0.14, 0.8], [6, 0.07, 0.5]];
        const total = partials.reduce((sum, [, share]) => sum + share, 0);
        const tone = createFilter(context, 'lowpass', Math.min(frequency * 8, 9000), output);
        const oscillators = [];

        partials.forEach(([factor, share, decay]) => {
            const gain = context.createGain();
            const node = createOscillator(context, 'sine', frequency * factor);

            gain.gain.setValueAtTime(SILENCE, start);
            gain.gain.exponentialRampToValueAtTime((peak * share) / total, start + 0.004);
            gain.gain.exponentialRampToValueAtTime(SILENCE, start + decay);
            gain.connect(tone);

            node.connect(gain);
            oscillators.push(node);
        });

        startAndStop(oscillators, start, start + 2.7);
    },

    // Frequenzmodulation mit abklingender Modulation: erst "Tine", dann weich.
    epiano(context, output, frequency, start, duration, peak) {
        const carrier = createOscillator(context, 'sine', frequency);
        const modulator = createOscillator(context, 'sine', frequency);
        const modulation = context.createGain();
        const gain = context.createGain();

        modulation.gain.setValueAtTime(frequency * 3, start);
        modulation.gain.exponentialRampToValueAtTime(frequency * 0.4, start + 0.7);
        modulator.connect(modulation).connect(carrier.frequency);

        gain.gain.setValueAtTime(SILENCE, start);
        gain.gain.exponentialRampToValueAtTime(peak, start + 0.005);
        gain.gain.exponentialRampToValueAtTime(SILENCE, start + 2.2);
        carrier.connect(gain).connect(output);

        startAndStop([carrier, modulator], start, start + 2.3);
    },

    synth(context, output, frequency, start, duration, peak) {
        const filter = createFilter(context, 'lowpass', 4000, output, 4);

        filter.frequency.setValueAtTime(4000, start);
        filter.frequency.exponentialRampToValueAtTime(900, start + 0.45);

        heldVoice(context, filter, start, duration, peak * 0.5, 0.01, 0.25, (input) => {
            const nodes = [
                createOscillator(context, 'sawtooth', frequency, -7),
                createOscillator(context, 'sawtooth', frequency, 7),
            ];

            nodes.forEach((node) => node.connect(input));

            return nodes;
        });
    },

    organ(context, output, frequency, start, duration, peak) {
        const harmonics = [[1, 1], [2, 0.8], [3, 0.6], [4, 0.5], [6, 0.3], [8, 0.2]];
        const total = harmonics.reduce((sum, [, share]) => sum + share, 0);

        heldVoice(context, output, start, duration, peak, 0.02, 0.12, (input) => harmonics.map(([factor, share]) => {
            const gain = context.createGain();
            const node = createOscillator(context, 'sine', frequency * factor);

            gain.gain.value = share / total;
            node.connect(gain).connect(input);

            return node;
        }));
    },

    strings(context, output, frequency, start, duration, peak) {
        const filter = createFilter(context, 'lowpass', 1500, output);

        heldVoice(context, filter, start, duration, peak * 0.4, 0.35, 0.9, (input) => {
            const nodes = [
                createOscillator(context, 'sawtooth', frequency, -11),
                createOscillator(context, 'sawtooth', frequency, 11),
                createOscillator(context, 'triangle', frequency / 2),
            ];

            nodes.forEach((node) => node.connect(input));

            return nodes;
        });
    },
};

const NOTE_NAMES = ['C', 'C♯', 'D', 'D♯', 'E', 'F', 'F♯', 'G', 'G♯', 'A', 'A♯', 'B'];

// Computertastatur wie ein Klavier: untere Reihe weiße, obere Reihe schwarze
// Tasten. Position im String = Halbton ab dem C.
const COMPUTER_KEYS = 'awsedftgyhujkolp';

const QUIZ_LEVELS = { easy: [1, 4, 5], medium: [1, 2, 4, 5, 6], hard: [1, 2, 3, 4, 5, 6, 7] };

const INVERSION_LABELS = ['Grundstellung', '1. Umkehrung', '2. Umkehrung', '3. Umkehrung'];

const settingKey = (name) => `chordFinder:${name}`;

// Komfort-Einstellungen leben nur in diesem Browser; ohne localStorage
// (z. B. privater Modus) greifen einfach die Standardwerte.
const loadSetting = (name) => {
    try {
        return localStorage.getItem(settingKey(name));
    } catch {
        return null;
    }
};

const saveSetting = (name, value) => {
    try {
        localStorage.setItem(settingKey(name), value);
    } catch {
        // siehe oben
    }
};

export default function chordFinder({ keys, progressions }) {
    return {
        // Das C4 (261,63 Hz) liegt auf Halbton 0.
        BASE_FREQUENCY: 261.6256,
        keys,
        progressions,

        mode: 'explore',
        keyId: 'C',
        degree: 1,
        inversion: 0,
        useSevenths: false,
        showScale: true,
        showChord: true,

        sound: 'piano',
        volume: 40,
        notation: 'english',
        audioContext: null,
        masterGain: null,
        masterOutput: null,
        copied: false,

        // Akkord bestimmen
        picked: [],
        identified: null,
        identifyRequest: 0,

        // Akkordfolgen
        progression: [],
        presetIndex: '',
        bpm: 80,
        loop: false,
        playingIndex: null,
        progressionTimer: null,

        // Hörtraining
        quiz: { level: 'easy', target: null, answered: false, answer: null, score: 0, total: 0, streak: 0 },
        quizTimer: null,

        // MIDI-Keyboard
        midiSupported: false,
        midiStatus: '',
        midiAccess: null,

        init() {
            this.midiSupported = 'requestMIDIAccess' in navigator;

            const savedSound = loadSetting('sound');

            if (savedSound && savedSound in voices) {
                this.sound = savedSound;
            }

            const savedVolume = parseInt(loadSetting('volume'), 10);

            if (savedVolume >= 0 && savedVolume <= 100) {
                this.volume = savedVolume;
            }

            if (['english', 'german'].includes(loadSetting('notation'))) {
                this.notation = loadSetting('notation');
            }

            this.readUrl();

            this.$watch('sound', (value) => saveSetting('sound', value));
            this.$watch('notation', (value) => saveSetting('notation', value));
            this.$watch('volume', (value) => {
                saveSetting('volume', value);
                this.applyVolume();
            });

            this.$watch('useSevenths', () => {
                this.inversion = Math.min(this.inversion, this.currentChord.notes.length - 1);
            });

            ['keyId', 'degree', 'inversion', 'useSevenths'].forEach((name) => {
                this.$watch(name, () => {
                    this.syncUrl();
                    this.$nextTick(() => this.scrollToHighlight());
                });
            });

            this.$watch('mode', () => {
                this.stopProgression();
                this.cancelQuizTimer();
                this.$nextTick(() => this.scrollToHighlight());
            });

            this.$watch('quiz.level', () => {
                this.quiz.target = null;
                this.quiz.answered = false;
            });
        },

        destroy() {
            this.stopProgression();
            this.cancelQuizTimer();
            this.disconnectMidi();
        },

        // ---- Auswahl und abgeleitete Werte -------------------------------

        get currentKey() {
            return this.keys.find((key) => key.id === this.keyId);
        },
        get majorKeys() {
            return this.keys.filter((key) => key.mode === 'major');
        },
        get minorKeys() {
            return this.keys.filter((key) => key.mode === 'minor');
        },
        get chordList() {
            return this.useSevenths ? this.currentKey.sevenths : this.currentKey.chords;
        },
        get currentChord() {
            return this.chordList[this.degree - 1];
        },
        get inversionLabels() {
            return INVERSION_LABELS.slice(0, this.currentChord.notes.length);
        },
        get presets() {
            return this.progressions[this.currentKey.mode];
        },

        // Der Akkord, der gerade auf der Klaviatur erscheint: im Hörtraining
        // die Aufgabe, bei der Akkordfolge der gerade spielende Akkord.
        get activeChord() {
            if (this.mode === 'quiz' && this.quiz.target) {
                return this.chordList[this.quiz.target - 1];
            }

            if (this.mode === 'progression' && this.playingIndex !== null) {
                return this.chordList[this.progression[this.playingIndex] - 1];
            }

            return this.currentChord;
        },
        get activeNotes() {
            return this.invert(this.activeChord.notes, this.mode === 'explore' ? this.inversion : 0);
        },
        get chordTitle() {
            const bass = this.inversion > 0 ? `/${this.activeNotes[0].note}` : '';

            return this.display(this.currentChord.name + bass);
        },
        get chordNotesText() {
            return this.activeNotes.map((note) => this.display(note.note)).join(' – ');
        },
        get scaleNotesText() {
            return this.currentKey.scale.map((note) => this.display(note.note)).join(' ');
        },

        // Umkehrung: der tiefste Ton wandert eine Oktave nach oben. Ragt der
        // Akkord dann über die Klaviatur hinaus, rutscht er eine Oktave runter.
        invert(notes, count) {
            let result = notes.map((note) => ({ ...note }));

            for (let step = 0; step < count; step++) {
                const lowest = result.shift();

                result.push({ ...lowest, semitone: lowest.semitone + 12 });
            }

            if (Math.max(...result.map((note) => note.semitone)) >= 24) {
                result = result.map((note) => ({ ...note, semitone: note.semitone - 12 }));
            }

            return result;
        },

        // Deutsche Notation: B wird zu H, B♭ zu B. Die Namen kommen vom Server
        // immer in englischer Schreibweise.
        display(name) {
            if (this.notation !== 'german' || !name) {
                return name;
            }

            return name.replace(/B(♭|♯|𝄪|𝄫)?/g, (match, accidental) => (accidental === '♭' ? 'B' : `H${accidental ?? ''}`));
        },

        // ---- Klaviatur ---------------------------------------------------

        // 'chord' schlägt 'scale': Akkordtöne gehören meist auch zur
        // Tonleiter, sollen dann aber in der Akkordfarbe erscheinen.
        state(semitone) {
            return this.marker(semitone)?.type ?? null;
        },

        // Was über einer Taste steht (Stufenzahl + Notenname). Die Tonleiter
        // läuft nur einmal von der Tonika aufwärts, der Akkord liegt dort, wo
        // seine Töne in der Tonleiter vorkommen (Terz/Quinte gern in der
        // nächsten Oktave).
        marker(semitone) {
            if (this.mode === 'identify') {
                return this.identifyMarker(semitone);
            }

            // Im Hörtraining bleibt der Akkord bis zur Antwort verborgen.
            const chordVisible = this.mode === 'quiz' ? this.quiz.answered : this.showChord;

            if (chordVisible) {
                const chordNote = this.activeNotes.find((note) => note.semitone === semitone);

                if (chordNote) {
                    return { type: 'chord', degree: chordNote.degree, note: this.display(chordNote.note) };
                }
            }

            if (this.showScale) {
                const scaleNote = this.currentKey.scale.find((note) => note.semitone === semitone);

                if (scaleNote) {
                    return { type: 'scale', degree: scaleNote.degree, note: this.display(scaleNote.note) };
                }
            }

            return null;
        },

        identifyMarker(semitone) {
            if (!this.picked.includes(semitone)) {
                return null;
            }

            const tones = this.identified?.tones ?? [];
            const exact = tones.find((tone) => tone.semitone === semitone);
            const samePitch = tones.find((tone) => tone.semitone % 12 === semitone % 12);

            return {
                type: 'chord',
                degree: exact?.role ?? '',
                note: this.display(samePitch?.note ?? NOTE_NAMES[semitone % 12]),
            };
        },

        // Direkt benachbarte Marker würden sich überlappen - jeder zweite
        // sitzt deshalb eine Reihe höher.
        isRaised(semitone) {
            return semitone > 0 && this.marker(semitone) !== null && this.marker(semitone - 1) !== null && !this.isRaised(semitone - 1);
        },

        noteName(semitone) {
            return this.display(NOTE_NAMES[semitone % 12]);
        },

        // Auf schmalen Displays ist die Klaviatur breiter als der Bildschirm und
        // wischbar - beim Wechsel von Tonart/Akkord die markierten Tasten ins
        // Bild holen.
        scrollToHighlight() {
            const scroller = this.$refs.scroller;

            if (!scroller || scroller.scrollWidth <= scroller.clientWidth) {
                return;
            }

            const semitones = (this.mode === 'identify' ? this.picked : this.showChord ? this.activeNotes : this.currentKey.scale)
                .map((entry) => entry.semitone ?? entry);

            if (semitones.length === 0) {
                return;
            }

            const first = scroller.querySelector(`[data-semitone="${Math.min(...semitones)}"]`);
            const last = scroller.querySelector(`[data-semitone="${Math.max(...semitones)}"]`);

            if (!first || !last) {
                return;
            }

            const middle = (first.getBoundingClientRect().left + last.getBoundingClientRect().right) / 2;
            const target = middle - scroller.getBoundingClientRect().left + scroller.scrollLeft - scroller.clientWidth / 2;

            scroller.scrollTo({ left: target, behavior: 'smooth' });
        },

        // Ein Klick auf eine Taste spielt den Ton; im Modus "Akkord bestimmen"
        // wählt er die Taste außerdem an oder ab.
        pressKey(semitone) {
            this.playNotes([semitone]);

            if (this.mode !== 'identify') {
                return;
            }

            this.picked = this.picked.includes(semitone)
                ? this.picked.filter((picked) => picked !== semitone)
                : [...this.picked, semitone].sort((a, b) => a - b);

            this.identify();
        },

        // ---- Akkord bestimmen -------------------------------------------

        identify() {
            if (this.picked.length === 0) {
                this.identified = null;

                return;
            }

            // Antworten können in anderer Reihenfolge eintreffen - nur die
            // jüngste Anfrage zählt.
            const request = ++this.identifyRequest;

            this.$wire.identify([...this.picked]).then((result) => {
                if (request === this.identifyRequest) {
                    this.identified = result;
                }
            });
        },

        resetPicked() {
            this.picked = [];
            this.identified = null;
            this.identifyRequest++;
        },

        playPicked() {
            if (this.picked.length > 0) {
                this.playNotes(this.picked);
            }
        },

        // ---- Abspielen ---------------------------------------------------

        playChord() {
            this.playNotes(this.activeNotes.map((note) => note.semitone));
        },

        playScale() {
            const root = this.currentKey.scale[0].semitone;

            this.playNotes([...this.currentKey.scale.map((note) => note.semitone), root + 12], 0.4);
        },

        playDegree(degree, duration = null) {
            this.playNotes(this.chordList[degree - 1].notes.map((note) => note.semitone), 0, duration);
        },

        ensureAudio() {
            if (this.audioContext) {
                return this.audioContext;
            }

            this.audioContext = new (window.AudioContext || window.webkitAudioContext)();

            // Mehrere Stimmen übereinander sollen nicht übersteuern.
            this.masterOutput = this.audioContext.createDynamicsCompressor();
            this.masterGain = this.audioContext.createGain();
            this.masterOutput.connect(this.masterGain).connect(this.audioContext.destination);
            this.applyVolume();

            return this.audioContext;
        },

        // Quadratische Kennlinie: Das Ohr empfindet Lautstärke nicht linear,
        // so wirkt der Regler gleichmäßiger.
        applyVolume() {
            if (this.masterGain) {
                this.masterGain.gain.value = (this.volume / 100) ** 2 * 0.8;
            }
        },

        // spacing = 0 spielt alle Töne gleichzeitig (Akkord), sonst nacheinander.
        playNotes(semitones, spacing = 0, duration = null) {
            const context = this.ensureAudio();

            // Browser starten den AudioContext erst nach einer Nutzeraktion.
            if (context.state === 'suspended') {
                context.resume();
            }

            const peak = Math.min(0.3, 0.6 / semitones.length);
            const holdFor = duration ?? (spacing > 0 ? 0.8 : 1.6);

            semitones.forEach((semitone, index) => {
                voices[this.sound](
                    context,
                    this.masterOutput,
                    this.BASE_FREQUENCY * 2 ** (semitone / 12),
                    context.currentTime + index * spacing,
                    holdFor,
                    peak,
                );
            });
        },

        // ---- Akkordfolgen ------------------------------------------------

        get isPlayingProgression() {
            return this.playingIndex !== null;
        },

        progressionLabel(degree) {
            const chord = this.chordList[degree - 1];

            return `${chord.number} ${this.display(chord.name)}`;
        },

        applyPreset() {
            if (this.presetIndex === '') {
                return;
            }

            this.stopProgression();
            this.progression = [...this.presets[this.presetIndex].degrees];
        },

        addToProgression(degree) {
            if (this.progression.length < 16) {
                this.progression.push(degree);
            }

            this.presetIndex = '';
            this.degree = degree;
            this.playDegree(degree);
        },

        removeFromProgression(index) {
            this.stopProgression();
            this.progression.splice(index, 1);
            this.presetIndex = '';
        },

        clearProgression() {
            this.stopProgression();
            this.progression = [];
            this.presetIndex = '';
        },

        // Jeder Akkord klingt zwei Schläge lang.
        playProgression() {
            if (this.progression.length === 0) {
                return;
            }

            this.stopProgression();

            const seconds = 120 / this.bpm;
            let index = 0;

            const step = () => {
                this.playingIndex = index;
                this.playDegree(this.progression[index], seconds);

                this.progressionTimer = setTimeout(() => {
                    index++;

                    if (index >= this.progression.length) {
                        if (!this.loop) {
                            this.stopProgression();

                            return;
                        }

                        index = 0;
                    }

                    step();
                }, seconds * 1000);
            };

            step();
        },

        stopProgression() {
            clearTimeout(this.progressionTimer);
            this.progressionTimer = null;
            this.playingIndex = null;
        },

        // ---- Hörtraining -------------------------------------------------

        get quizDegrees() {
            return QUIZ_LEVELS[this.quiz.level];
        },
        get quizFeedback() {
            if (!this.quiz.answered) {
                return '';
            }

            const target = this.chordList[this.quiz.target - 1];

            return this.quiz.answer === this.quiz.target
                ? `Richtig! ${target.number} = ${this.display(target.name)}`
                : `Leider falsch – es war ${target.number} = ${this.display(target.name)}`;
        },

        cancelQuizTimer() {
            clearTimeout(this.quizTimer);
            this.quizTimer = null;
        },

        // Erst die Tonika als Orientierung, dann der gesuchte Akkord.
        startQuiz() {
            const pool = this.quizDegrees;
            let next;

            do {
                next = pool[Math.floor(Math.random() * pool.length)];
            } while (next === this.quiz.target && pool.length > 1);

            this.quiz.target = next;
            this.quiz.answered = false;
            this.quiz.answer = null;
            this.replayQuiz();
        },

        replayQuiz() {
            if (!this.quiz.target) {
                return;
            }

            this.cancelQuizTimer();
            this.playDegree(1);
            this.quizTimer = setTimeout(() => this.playDegree(this.quiz.target), 1800);
        },

        answerQuiz(degree) {
            if (!this.quiz.target || this.quiz.answered) {
                return;
            }

            this.cancelQuizTimer();
            this.quiz.answered = true;
            this.quiz.answer = degree;
            this.quiz.total++;

            if (degree === this.quiz.target) {
                this.quiz.score++;
                this.quiz.streak++;
            } else {
                this.quiz.streak = 0;
            }

            this.playDegree(this.quiz.target);
        },

        resetQuizScore() {
            Object.assign(this.quiz, { score: 0, total: 0, streak: 0 });
        },

        // ---- Link teilen -------------------------------------------------

        readUrl() {
            const params = new URLSearchParams(window.location.search);
            const keyId = params.get('tonart');
            const degree = parseInt(params.get('stufe'), 10);
            const inversion = parseInt(params.get('umkehrung'), 10);

            if (keyId && this.keys.some((key) => key.id === keyId)) {
                this.keyId = keyId;
            }

            if (degree >= 1 && degree <= 7) {
                this.degree = degree;
            }

            this.useSevenths = params.get('vierklang') === '1';

            if (inversion > 0 && inversion < this.currentChord.notes.length) {
                this.inversion = inversion;
            }
        },

        // Hält die Adresszeile auf dem Stand des Finders, damit man sie
        // direkt kopieren kann. Nur im Finder - die anderen Modi haben
        // keinen teilbaren Zustand.
        syncUrl() {
            if (this.mode !== 'explore') {
                return;
            }

            window.history.replaceState(window.history.state, '', this.shareUrl());
        },

        shareUrl() {
            const params = new URLSearchParams({ tonart: this.keyId, stufe: this.degree });

            if (this.useSevenths) {
                params.set('vierklang', '1');
            }

            if (this.inversion > 0) {
                params.set('umkehrung', this.inversion);
            }

            return `${window.location.origin}${window.location.pathname}?${params}`;
        },

        copyLink() {
            navigator.clipboard?.writeText(this.shareUrl()).then(() => {
                this.copied = true;
                setTimeout(() => (this.copied = false), 2000);
            }).catch(() => {});
        },

        // ---- Computertastatur --------------------------------------------

        handleKey(event) {
            if (event.ctrlKey || event.metaKey || event.altKey || event.repeat) {
                return;
            }

            // In Formularfeldern soll normal getippt werden können.
            if (event.target.closest?.('input, select, textarea, [contenteditable]')) {
                return;
            }

            const key = event.key.toLowerCase();

            if (this.mode !== 'identify' && /^[1-7]$/.test(key)) {
                this.degree = parseInt(key, 10);
                this.playDegree(this.degree);

                return;
            }

            // Leertaste löst auf einem fokussierten Button den Button aus.
            if (key === ' ' && !event.target.closest?.('button, a')) {
                event.preventDefault();

                if (this.mode === 'identify') {
                    this.playPicked();
                } else {
                    this.playChord();
                }

                return;
            }

            const semitone = COMPUTER_KEYS.indexOf(key);

            if (semitone >= 0 && key.length === 1) {
                this.pressKey(semitone);
            }
        },

        // ---- MIDI-Keyboard -----------------------------------------------

        connectMidi() {
            if (!this.midiSupported) {
                return;
            }

            navigator.requestMIDIAccess().then((access) => {
                this.midiAccess = access;
                access.onstatechange = () => this.bindMidiInputs();
                this.bindMidiInputs();
            }).catch(() => {
                this.midiStatus = 'Zugriff auf MIDI wurde nicht erlaubt.';
            });
        },

        bindMidiInputs() {
            const inputs = [...this.midiAccess.inputs.values()];

            inputs.forEach((input) => {
                input.onmidimessage = (event) => this.handleMidi(event.data);
            });

            this.midiStatus = inputs.length > 0
                ? `Verbunden: ${inputs.map((input) => input.name).join(', ')}`
                : 'Kein MIDI-Gerät gefunden.';
        },

        disconnectMidi() {
            this.midiAccess?.inputs.forEach((input) => {
                input.onmidimessage = null;
            });
        },

        // Die Klaviatur zeigt C4 bis H5 (MIDI-Noten 60–83); andere Lagen
        // werden in diesen Bereich gefaltet.
        handleMidi([status, note, velocity]) {
            const command = status & 0xf0;
            const semitone = (((note - 60) % 24) + 24) % 24;
            const isNoteOn = command === 0x90 && velocity > 0;
            const isNoteOff = command === 0x80 || (command === 0x90 && velocity === 0);

            if (isNoteOn) {
                this.playNotes([semitone]);

                if (this.mode === 'identify' && !this.picked.includes(semitone)) {
                    this.picked = [...this.picked, semitone].sort((a, b) => a - b);
                    this.identify();
                }
            } else if (isNoteOff && this.mode === 'identify') {
                // Gehaltene Töne bilden den Akkord; nach dem Loslassen bleibt
                // der zuletzt erkannte Akkord im Ergebnis stehen.
                this.picked = this.picked.filter((picked) => picked !== semitone);

                if (this.picked.length > 0) {
                    this.identify();
                }
            }
        },
    };
}
