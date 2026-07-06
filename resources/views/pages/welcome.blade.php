{{-- resources/views/welcome.blade.php --}}
<x-layouts.app>
    <x-slot:head>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    </x-slot:head>
    
    <div class="fixed inset-2 flex items-center justify-center border-2 border-dashed border-neutral-500">
        <div class="text-center font-mono text-2xl text-neutral-500">
            soon&trade; | skratter.com

            <div class="mt-2 flex justify-center gap-3 text-base">
                <a href="https://www.instagram.com/skratter_com/" target="_blank" rel="noopener"
                    class="text-neutral-500 hover:text-blue-900">
                    <i class="fa fa-instagram"></i>
                </a>
                <a href="https://www.facebook.com/skrattercom/" target="_blank" rel="noopener"
                    class="text-neutral-500 hover:text-blue-900">
                    <i class="fa fa-facebook"></i>
                </a>
                <a href="https://www.flickr.com/photos/skratter" target="_blank" rel="noopener"
                    class="text-neutral-500 hover:text-blue-900">
                    <i class="fa fa-flickr"></i>
                </a>
            </div>
        </div>
    </div>
</x-layouts.app>
