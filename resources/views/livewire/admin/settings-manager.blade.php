<div class="max-w-lg space-y-6">
    <flux:heading size="xl">Einstellungen</flux:heading>

    <form wire:submit="save" class="space-y-6">
        <flux:field>
            <flux:label>Seitenname</flux:label>
            <flux:input wire:model="form.site_name" />
            <flux:description>Wird im Header, im Browser-Tab und als Platzhalter-Titel angezeigt.</flux:description>
            <flux:error name="form.site_name" />
        </flux:field>

        <flux:field>
            <flux:label>Anzahl Startseitenfotos</flux:label>
            <flux:input type="number" wire:model="form.homepage_photo_count" />
            <flux:description>Wie viele Fotos gleichzeitig auf der Startseite gezeigt werden.</flux:description>
            <flux:error name="form.homepage_photo_count" />
        </flux:field>

        <flux:field>
            <flux:label>Rotationsintervall Startseite (Sekunden)</flux:label>
            <flux:input type="number" wire:model="form.homepage_rotate_seconds" />
            <flux:description>Wie oft ein Foto auf der Startseite automatisch gegen ein noch nicht gezeigtes ausgetauscht wird.</flux:description>
            <flux:error name="form.homepage_rotate_seconds" />
        </flux:field>

        <flux:field>
            <flux:label>Diashow-Geschwindigkeit (Sekunden)</flux:label>
            <flux:input type="number" wire:model="form.slideshow_autoplay_seconds" />
            <flux:description>Wie schnell die Diashow (Autoplay-Button in der Foto-Ansicht) weiterschaltet.</flux:description>
            <flux:error name="form.slideshow_autoplay_seconds" />
        </flux:field>

        <flux:field>
            <flux:label>Fotos pro Seite in Alben</flux:label>
            <flux:input type="number" wire:model="form.album_photos_per_page" />
            <flux:description>Wie viele Fotos in der Album-Ansicht gezeigt werden, bevor auf die nächste Seite geblättert werden muss.</flux:description>
            <flux:error name="form.album_photos_per_page" />
        </flux:field>

        <flux:field>
            <flux:label>Spalten im Foto-Raster (Desktop)</flux:label>
            <flux:input type="number" wire:model="form.masonry_columns" />
            <flux:description>Gilt für breitere Bildschirme; auf schmalen Bildschirmen werden es höchstens 2.</flux:description>
            <flux:error name="form.masonry_columns" />
        </flux:field>

        <flux:field>
            <flux:label>Instagram-Link</flux:label>
            <flux:input type="url" wire:model="form.social_instagram_url" placeholder="https://www.instagram.com/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_instagram_url" />
        </flux:field>

        <flux:field>
            <flux:label>LinkedIn-Link</flux:label>
            <flux:input type="url" wire:model="form.social_linkedin_url" placeholder="https://www.linkedin.com/in/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_linkedin_url" />
        </flux:field>

        <flux:field>
            <flux:label>Facebook-Link</flux:label>
            <flux:input type="url" wire:model="form.social_facebook_url" placeholder="https://www.facebook.com/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_facebook_url" />
        </flux:field>

        <flux:field>
            <flux:label>Flickr-Link</flux:label>
            <flux:input type="url" wire:model="form.social_flickr_url" placeholder="https://www.flickr.com/photos/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_flickr_url" />
        </flux:field>

        <flux:field>
            <flux:label>X-Link</flux:label>
            <flux:input type="url" wire:model="form.social_x_url" placeholder="https://x.com/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_x_url" />
        </flux:field>

        <flux:field>
            <flux:label>YouTube-Link</flux:label>
            <flux:input type="url" wire:model="form.social_youtube_url" placeholder="https://www.youtube.com/@dein-kanal" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_youtube_url" />
        </flux:field>

        <flux:field>
            <flux:label>Pinterest-Link</flux:label>
            <flux:input type="url" wire:model="form.social_pinterest_url" placeholder="https://www.pinterest.com/dein-profil" />
            <flux:description>Leer lassen, um das Icon in der Kopfzeile auszublenden.</flux:description>
            <flux:error name="form.social_pinterest_url" />
        </flux:field>

        <flux:field>
            <flux:label>Eigene Domains (Auswertung)</flux:label>
            <flux:textarea wire:model="form.analytics_own_domains" rows="4" placeholder="eine-domain.de&#10;www.eine-domain.de" />
            <flux:description>Eine Domain pro Zeile. Referrer, die hierauf passen, werden in der Auswertung als „eigene Domain" markiert statt als externe Verlinkung gewertet.</flux:description>
            <flux:error name="form.analytics_own_domains" />
        </flux:field>

        <flux:button type="submit" variant="primary">Speichern</flux:button>
    </form>
</div>
