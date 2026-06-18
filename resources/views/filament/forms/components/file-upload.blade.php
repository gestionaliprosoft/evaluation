@foreach ($getState() as $file)
    @php
        $team = 'team-'.$file['team_id'];
        $folder = Str::afterLast($file['attachable_type'], '\\');
        $filename = $file['filename'];
        $originalFilename = $file['original_filename'];
        $description = $file['description'];
        $disk = 'private';
        $extension = Str::lower(Str::afterLast($filename, '.'));
    @endphp


    <x-filament::section style="margin-bottom:10px;">
        <x-slot name="heading">
            {{ $originalFilename }}
        </x-slot>

        <x-slot name="description">
            {{ $description ?? '' }}
        </x-slot>

        @if (collect(config('filament-browser.mimes_images'))->contains($extension))
            <img
                src="{{ url('/file/'.$team.'/'.$folder.'/'.$filename.'/image/'.$disk) }}"
                alt=""
                class=""
            >
        @elseif (collect(config('filament-browser.mimes_video'))->contains($extension))
            <video controls width="100%" height="300px">
                <source src="{{ url('/file/'.$team.'/'.$folder.'/'.$filename.'/video/'.$disk) }}" type="video/{{ $extension }}">
            </video>
        @elseif (collect(config('filament-browser.mimes_audio'))->contains($extension))
            <audio controls width="100%">
                <source src="{{ url('/file/'.$team.'/'.$folder.'/'.$filename.'/audio/'.$disk) }}" type="audio/{{ $extension }}">
            </audio>
        @elseif (collect(config('filament-browser.mimes_pdf'))->contains($extension))
            <div class="w-full h-full">
                <iframe src="{{ url('/file/'.$team.'/'.$folder.'/'.$filename.'/pdf/'.$disk) }}" width="100%" height="600px"></iframe>
            </div>
        @else
            <x-bxs-file-blank v-else class="w-10 h-10" style="color: #22b8d6 !important;"/>
        @endif

        @if ($operation == 'Edit')
            @can('download_'.$permission)
                <x-slot name="headerEnd">
                    <x-filament::icon-button
                        size="sm"
                        icon="heroicon-m-folder-arrow-down"
                        href="{{ url('/file/'.$team.'/'.$folder.'/'.$filename.'/download/'.$disk) }}"
                        tag="a"
                        tooltip="Download"
                        download="{{ $filename }}"
                    />
                </x-slot>
            @endcan
        @endif
    </x-filament::section>
@endforeach
