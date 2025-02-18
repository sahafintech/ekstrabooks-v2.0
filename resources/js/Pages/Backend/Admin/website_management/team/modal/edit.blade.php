<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Website" page="team" subpage="list" />

        <form method="post" class="ajax-submit" autocomplete="off" action="{{ route('teams.store') }}" enctype="multipart/form-data">
            {{ csrf_field() }}
            <input name="_method" type="hidden" value="PATCH">
            <div class="box">
                <div class="box-header">
                    <h5>{{ _lang('Teams') }}</h5>
                </div>
                <div class="box-body">
                    <div class="grid grid-cols-12 gap-x-5">
                        <div class="md:col-span-6 col-span-12">
                            <x-input-label value="{{ _lang('Name') }}" />
                            <x-text-input type="text" name="trans[name]" value="{{ $team->translation->name }}" required />
                        </div>

                        <div class="md:col-span-6 col-span-12 md:mt-0 mt-3">
                            <x-input-label value="{{ _lang('Role') }}" />
                            <x-text-input type="text" name="trans[role]" value="{{ $team->translation->role }}" required />
                        </div>

                        <div class="col-span-12 mt-3">
                            <x-input-label value="{{ _lang('Image') }}" />
                            <x-text-input type="file" class="dropify" name="image" data-default-file="{{ $team->image != '' ? asset('/uploads/media/'.$team->image) : '' }}" />
                        </div>

                        <div class="md:col-span-6 col-span-12 mt-3">
                            <x-input-label value="{{ _lang('Description') }}" />
                            <textarea class="w-full" name="trans[description]">{{ $team->translation->description }}</textarea>
                        </div>

                        <div class="md:col-span-6 col-span-12 mt-3">
                            <x-input-label value="{{ _lang('Language') }}" />
                            <select class="w-full" name="model_language" required>
                                {{ load_language(get_language()) }}
                            </select>
                        </div>

                        <div class="col-span-12 mt-4">
                            <x-primary-button type="submit" class="submit-btn">{{ _lang('Save') }}</x-primary-button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>