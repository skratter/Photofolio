import Trix from 'trix';
import { marked } from 'marked';

document.addEventListener('alpine:init', () => {
    Alpine.data('photoMasonry', (photos, pool = [], rotateSeconds = null, autoplaySeconds = 3, desktopColumns = 3) => ({
        index: null,
        photos,
        pool,
        laidOut: false,
        autoplayTimer: null,

        init() {
            this.$nextTick(() => this.layoutMasonry());

            // Only the homepage passes a pool + interval - regular album
            // pages show every photo and never rotate.
            if (!rotateSeconds || this.pool.length === 0) {
                return;
            }

            setInterval(() => this.rotateOne(), rotateSeconds * 1000);
        },

        open(i) {
            this.index = i;
        },
        close() {
            this.index = null;
            this.stopAutoplay();
        },
        next() {
            this.index = (this.index + 1) % this.photos.length;
        },
        prev() {
            this.index = (this.index - 1 + this.photos.length) % this.photos.length;
        },

        get isAutoplaying() {
            return this.autoplayTimer !== null;
        },

        toggleAutoplay() {
            if (this.isAutoplaying) {
                this.stopAutoplay();

                return;
            }

            this.autoplayTimer = setInterval(() => this.next(), autoplaySeconds * 1000);
        },

        stopAutoplay() {
            clearInterval(this.autoplayTimer);
            this.autoplayTimer = null;
        },

        // CSS columns (the previous approach) fills one column top-to-bottom
        // before starting the next, so the bottom edge ends up ragged - one
        // column can easily run noticeably longer than the others. This
        // instead does a real shortest-column-first placement: each photo
        // goes into whichever column is currently shortest, using the known
        // width/height to compute sizes without waiting for images to load.
        // Positions items absolutely, so it also has to size/place them all
        // itself instead of relying on normal document flow.
        layoutMasonry() {
            const container = this.$refs.grid;

            // A zero width means the container isn't actually laid out yet
            // (e.g. still hidden, or this ran before its own CSS applied) -
            // computing positions from that would poison every column height
            // with an invalid number. Retry next frame instead of leaving
            // the grid stuck uninitialized until something else (a resize)
            // happens to trigger a proper run.
            if (!container) {
                return;
            }

            if (container.offsetWidth === 0) {
                requestAnimationFrame(() => this.layoutMasonry());

                return;
            }

            const gap = 16;
            const columns = window.innerWidth >= 640 ? desktopColumns : Math.min(2, desktopColumns);
            const columnWidth = (container.offsetWidth - gap * (columns - 1)) / columns;
            const columnHeights = new Array(columns).fill(0);
            const lastItemPerColumn = new Array(columns).fill(null);

            this.photos.forEach((photo, i) => {
                const item = container.querySelector(`[data-slot="${i}"]`);

                // A missing/zero width or height would make itemHeight NaN -
                // and once one column's height is NaN, "find the shortest
                // column" (comparing against NaN) can never resolve again,
                // silently collapsing every remaining photo onto the same
                // spot. Skip that one tile instead of corrupting the rest.
                if (!item || !photo.width || !photo.height) {
                    return;
                }

                const column = columnHeights.indexOf(Math.min(...columnHeights));
                const top = columnHeights[column];
                const itemHeight = columnWidth / (photo.width / photo.height);

                item.style.width = `${columnWidth}px`;
                item.style.height = `${itemHeight}px`;
                item.style.left = `${column * (columnWidth + gap)}px`;
                item.style.top = `${top}px`;

                columnHeights[column] += itemHeight + gap;
                lastItemPerColumn[column] = { item, top, height: itemHeight };
            });

            this.laidOut = true;

            // With only a handful of photos per column, they rarely add up to
            // exactly the same height - stretching the last tile in every
            // shorter column down to the tallest column's bottom edge
            // (object-cover just crops a little more of that one photo)
            // makes the grid finish flush instead of leaving a ragged gap.
            const bottom = Math.max(0, ...columnHeights) - gap;

            lastItemPerColumn.forEach((last) => {
                if (last) {
                    last.item.style.height = `${bottom - last.top}px`;
                }
            });

            container.style.height = `${bottom}px`;
        },

        // Fades one random visible photo out, swaps it for a random one from
        // the pool that isn't currently shown, then fades the new one in and
        // relayouts (the incoming photo's aspect ratio may differ from the
        // outgoing one's, so later items in that column may need to shift).
        rotateOne() {
            if (this.pool.length === 0) {
                return;
            }

            const slot = Math.floor(Math.random() * this.photos.length);
            const img = this.$el.querySelector(`[data-slot="${slot}"] img`);

            if (!img) {
                return;
            }

            img.classList.remove('opacity-100');
            img.classList.add('opacity-0');

            setTimeout(() => {
                const poolIndex = Math.floor(Math.random() * this.pool.length);
                const incoming = this.pool.splice(poolIndex, 1)[0];
                const outgoing = this.photos[slot];

                this.pool.push(outgoing);
                this.photos[slot] = incoming;
                this.layoutMasonry();

                img.alt = incoming.title ?? '';
                img.onload = () => {
                    img.classList.remove('opacity-0');
                    img.classList.add('opacity-100');
                };
                img.src = incoming.thumb;
            }, 700); // matches the CSS transition-opacity duration on the <img>
        },
    }));

    // Livewire uploads every file bound to the same wire:model property in
    // ONE combined request - which runs straight into PHP's max_file_uploads
    // (20 by default) once more than 20 files are selected at once, with no
    // indication of which files made it in and which didn't. This splits the
    // selection into small batches and uploads them one at a time through a
    // hidden input that only this component ever touches, tracking each
    // file's status as its batch comes back from the server.
    Alpine.data('photoUploader', () => ({
        BATCH_SIZE: 15,
        queue: [],
        batches: [],
        batchIndex: 0,
        isDraggingOver: false,
        isUploading: false,
        progress: 0,

        get total() {
            return this.queue.length;
        },
        get finished() {
            return this.queue.filter((file) => file.status === 'fertig' || file.status === 'fehler').length;
        },

        handleFiles(fileList) {
            const files = Array.from(fileList);

            if (files.length === 0) {
                return;
            }

            this.queue = files.map((file) => ({ name: file.name, status: 'wartend' }));
            this.batches = [];

            for (let i = 0; i < files.length; i += this.BATCH_SIZE) {
                this.batches.push(files.slice(i, i + this.BATCH_SIZE));
            }

            this.batchIndex = 0;
            this.uploadNextBatch();
        },

        uploadNextBatch() {
            if (this.batchIndex >= this.batches.length) {
                return;
            }

            const batch = this.batches[this.batchIndex];

            batch.forEach((file) => {
                const entry = this.queue.find((queued) => queued.name === file.name && queued.status === 'wartend');

                if (entry) {
                    entry.status = 'laedt';
                }
            });

            const dataTransfer = new DataTransfer();
            batch.forEach((file) => dataTransfer.items.add(file));

            this.$refs.uploader.files = dataTransfer.files;
            this.$refs.uploader.dispatchEvent(new Event('change'));
        },

        handleBatchUploaded(results) {
            results.forEach(({ name, status }) => {
                const entry = this.queue.find((queued) => queued.name === name && queued.status === 'laedt');

                if (entry) {
                    entry.status = status === 'done' ? 'fertig' : 'fehler';
                }
            });

            this.batchIndex++;
            this.uploadNextBatch();
        },
    }));

    // Die Klänge werden live mit Web Audio synthetisiert (keine Sample-Dateien).
    // Jede Stimme bekommt Kontext, Ausgang, Frequenz, Startzeit, Haltedauer und
    // Lautstärke. Schlagende Klänge (Klavier, E-Piano) klingen von selbst ab,
    // gehaltene (Synth, Orgel, Streicher) bleiben bis zur Haltedauer stehen.
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

    const chordFinderVoices = {
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

    // Akkordfinder: Tonleitern und Akkorde kommen fertig berechnet vom Server
    // (BuildChordFinderDataAction), Halbtöne gezählt ab dem C der ersten
    // Oktave. Hier passiert nur Auswahl, Hervorhebung und Ton.
    Alpine.data('chordFinder', (keys) => ({
        NOTE_NAMES: ['C', 'C♯', 'D', 'D♯', 'E', 'F', 'F♯', 'G', 'G♯', 'A', 'A♯', 'B'],
        // Das C4 (261,63 Hz) liegt auf Halbton 0.
        BASE_FREQUENCY: 261.6256,
        keys,
        keyId: 'C',
        degree: 1,
        showScale: true,
        showChord: true,
        audioContext: null,
        masterOutput: null,
        sound: 'piano',

        init() {
            // Der Klang ist eine reine Komfort-Einstellung dieses Browsers.
            try {
                const savedSound = localStorage.getItem('chordFinderSound');

                if (savedSound && savedSound in chordFinderVoices) {
                    this.sound = savedSound;
                }
            } catch {
                // Kein Zugriff auf localStorage (z. B. privater Modus): Standardklang bleibt.
            }

            this.$watch('sound', (value) => {
                try {
                    localStorage.setItem('chordFinderSound', value);
                } catch {
                    // siehe oben
                }
            });

            this.$watch('keyId', () => this.$nextTick(() => this.scrollToHighlight()));
            this.$watch('degree', () => this.$nextTick(() => this.scrollToHighlight()));
        },

        // Auf schmalen Displays ist die Klaviatur breiter als der Bildschirm und
        // wischbar - beim Wechsel von Tonart/Akkord die markierten Tasten ins
        // Bild holen.
        scrollToHighlight() {
            const scroller = this.$refs.scroller;

            if (!scroller || scroller.scrollWidth <= scroller.clientWidth) {
                return;
            }

            const semitones = (this.showChord ? this.currentChord.notes : this.currentKey.scale).map((note) => note.semitone);
            const first = scroller.querySelector(`[data-semitone="${Math.min(...semitones)}"]`);
            const last = scroller.querySelector(`[data-semitone="${Math.max(...semitones)}"]`);

            if (!first || !last) {
                return;
            }

            const middle = (first.getBoundingClientRect().left + last.getBoundingClientRect().right) / 2;
            const target = middle - scroller.getBoundingClientRect().left + scroller.scrollLeft - scroller.clientWidth / 2;

            scroller.scrollTo({ left: target, behavior: 'smooth' });
        },

        get currentKey() {
            return this.keys.find((key) => key.id === this.keyId);
        },
        get currentChord() {
            return this.currentKey.chords[this.degree - 1];
        },
        get chordNotesText() {
            return this.currentChord.notes.map((note) => note.note).join(' – ');
        },
        get scaleNotesText() {
            return this.currentKey.scale.map((note) => note.note).join(' ');
        },

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
            if (this.showChord) {
                const chordNote = this.currentChord.notes.find((note) => note.semitone === semitone);

                if (chordNote) {
                    return { type: 'chord', degree: chordNote.degree, note: chordNote.note };
                }
            }

            if (this.showScale) {
                const scaleNote = this.currentKey.scale.find((note) => note.semitone === semitone);

                if (scaleNote) {
                    return { type: 'scale', degree: scaleNote.degree, note: scaleNote.note };
                }
            }

            return null;
        },

        // Direkt benachbarte Marker würden sich überlappen - jeder zweite
        // sitzt deshalb eine Reihe höher.
        isRaised(semitone) {
            return semitone > 0 && this.marker(semitone) !== null && this.marker(semitone - 1) !== null && !this.isRaised(semitone - 1);
        },

        noteName(semitone) {
            return this.NOTE_NAMES[semitone % 12];
        },

        playKey(semitone) {
            this.playNotes([semitone]);
        },

        playChord() {
            this.playNotes(this.currentChord.notes.map((note) => note.semitone));
        },

        playScale() {
            const root = this.currentKey.scale[0].semitone;

            this.playNotes([...this.currentKey.scale.map((note) => note.semitone), root + 12], 0.4);
        },

        // spacing = 0 spielt alle Töne gleichzeitig (Akkord), sonst nacheinander.
        playNotes(semitones, spacing = 0) {
            if (!this.audioContext) {
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();

                // Mehrere Stimmen übereinander sollen nicht übersteuern.
                this.masterOutput = this.audioContext.createDynamicsCompressor();
                this.masterOutput.connect(this.audioContext.destination);
            }

            const context = this.audioContext;

            // Browser starten den AudioContext erst nach einer Nutzeraktion.
            if (context.state === 'suspended') {
                context.resume();
            }

            const peak = Math.min(0.3, 0.6 / semitones.length);
            const duration = spacing > 0 ? 0.8 : 1.6;

            semitones.forEach((semitone, index) => {
                chordFinderVoices[this.sound](
                    context,
                    this.masterOutput,
                    this.BASE_FREQUENCY * 2 ** (semitone / 12),
                    context.currentTime + index * spacing,
                    duration,
                    peak,
                );
            });
        },
    }));
});

document.addEventListener('trix-before-initialize', () => {
    // Only one heading level ships by default - map it to <h2> since <h1> is
    // reserved for the page title itself.
    Trix.config.blockAttributes.heading1.tagName = 'h2';
});

// Trix has no Markdown support of its own. Pasted Markdown (e.g. an
// AI-drafted text with "## Headings" and "**bold**") would otherwise show up
// as literal '#'/'*' characters. Browsers put a "text/html" flavor on the
// clipboard for nearly any copy from a web page (even a plain-looking code
// block), and Trix's own trix-paste event already prefers that HTML flavor
// over plain text - so listening for "text/plain" there never actually
// fires for copies made from a browser. Intercepting the native "paste"
// event here (capture phase, before Trix's own listener runs) lets us read
// the clipboard ourselves and decide based on the content, not on which
// flavor happened to win.
const MARKDOWN_PATTERN = /^#{1,6}\s|\*\*[^*]+\*\*|^>\s|`[^`]+`|\[[^\]]+\]\([^)]+\)/m;

document.addEventListener('paste', (event) => {
    // With existing content already in the editor, the caret can sit inside
    // a nested block element, and the browser may target the paste event at
    // that descendant instead of the <trix-editor> host itself - so this
    // walks up to find it instead of requiring an exact match.
    const trixEditor = event.target.closest ? event.target.closest('trix-editor') : null;

    if (!trixEditor) {
        return;
    }

    const text = event.clipboardData?.getData('text/plain');

    if (!text || !MARKDOWN_PATTERN.test(text)) {
        return;
    }

    event.preventDefault();
    event.stopImmediatePropagation();

    // breaks: true turns single line breaks into <br> - plain CommonMark
    // otherwise only treats a blank line as a paragraph break, silently
    // joining e.g. separate address lines into one.
    const html = marked.parse(text, { breaks: true });
    const editor = trixEditor.editor;

    // editor.insertHTML() merges the parsed HTML into the document at the
    // current selection - and when that selection starts inside an existing
    // formatted block (e.g. after selecting all existing text, which starts
    // inside whatever block is first), Trix wraps the *entire* newly
    // inserted multi-block HTML in that block's formatting instead of
    // respecting each new block's own tag. Confirmed via logging: the
    // converted HTML itself was always correct, only insertHTML() mangled
    // it. When the whole document is selected (or it's empty - the common
    // case here, replacing/setting a page's full content), loadHTML() loads
    // fresh content directly with no merge step, sidestepping the bug
    // entirely. Only fall back to insertHTML() for a genuine partial-content
    // paste in the middle of otherwise untouched text.
    const [rangeStart, rangeEnd] = editor.getSelectedRange();
    const documentLength = editor.getDocument().getLength();
    const wholeDocumentSelected = rangeStart === 0 && rangeEnd >= documentLength - 1;

    if (wholeDocumentSelected) {
        editor.loadHTML(html);
    } else {
        editor.insertHTML(html);
    }
}, true);

/**
 * Uploads a Trix file attachment to the page-attachments endpoint and
 * finalizes it with the resulting public URL. If the page doesn't exist yet
 * (still composing a brand new page), the server creates a draft row and
 * returns its id via onPageCreated so the caller can track it.
 */
window.uploadPageAttachment = function (event, pageId, onPageCreated) {
    const attachment = event.attachment;

    if (!attachment.file) {
        return;
    }

    const formData = new FormData();
    formData.append('file', attachment.file);

    if (pageId) {
        formData.append('page_id', pageId);
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', '/admin/pages/attachments', true);
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);
    xhr.setRequestHeader('Accept', 'application/json');

    xhr.upload.addEventListener('progress', (progressEvent) => {
        if (progressEvent.lengthComputable) {
            attachment.setUploadProgress((progressEvent.loaded / progressEvent.total) * 100);
        }
    });

    xhr.addEventListener('load', () => {
        if (xhr.status >= 200 && xhr.status < 300) {
            const data = JSON.parse(xhr.responseText);
            attachment.setAttributes({ url: data.url, href: data.url });

            if (onPageCreated && data.page_id) {
                onPageCreated(data.page_id);
            }
        }
    });

    xhr.send(formData);
};
