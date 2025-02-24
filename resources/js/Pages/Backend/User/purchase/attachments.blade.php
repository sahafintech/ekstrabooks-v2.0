<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
        <thead>
            <tr>
                <th>File Name</th>
                <th>Attachment</th>
                <th>Download</th>
            </tr>
        </thead>
        <tbody>
            @foreach($attachments as $attachment)
            @php
            $extension = pathinfo($attachment->path, PATHINFO_EXTENSION);
            @endphp
            <tr>
                <td>{{ $attachment->file_name }}</td>
                <td>
                    @switch($extension)
                    @case('pdf')
                    <i class="ri-file-pdf-line text-lg text-danger"></i>
                    @break
                    @case('doc')
                    <i class="ri-file-word-line text-lg text-primary"></i>
                    @break
                    @case('docx')
                    <i class="ri-file-word-line text-lg text-primary"></i>
                    @break
                    @case('xls')
                    <i class="ri-file-excel-2-line text-lg text-success"></i>
                    @break
                    @case('xlsx')
                    <i class="ri-file-excel-2-line text-lg text-success"></i>
                    @break
                    @case('ppt')
                    <i class="ri-file-ppt-line text-lg text-warning"></i>
                    @break
                    @case('pptx')
                    <i class="ri-file-ppt-line text-lg text-warning"></i>
                    @break
                    @case('zip')
                    <i class="ri-zip-line text-lg text-info"></i>
                    @break
                    @case('rar')
                    <i class="ri-zip-line text-lg text-info"></i>
                    @break
                    @case('7z')
                    <i class="ri-zip-line text-lg text-info"></i>
                    @break
                    @case('txt')
                    <i class="ri-file-text-line text-lg text-secondary"></i>
                    @break
                    @case('jpg')
                    <i class="ri-image-line text-lg text-info"></i>
                    @break
                    @case('jpeg')
                    <i class="ri-image-line text-lg text-info"></i>
                    @break
                    @case('png')
                    <i class="ri-image-line text-lg text-info"></i>
                    @break
                    @case('gif')
                    <i class="ri-image-line text-lg text-info"></i>
                    @break
                    @case('svg')
                    <i class="ri-image-line text-lg text-info"></i>
                    @break
                    @default
                    <i class="ri-file-line text-lg text-secondary"></i>
                    @endswitch
                </td>
                <td>
                    <a href="{{ $attachment->path }}" download="$attachment->path">
                        <i class="ri-file-download-line text-lg text-success"></i>
                    </a>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
</div>