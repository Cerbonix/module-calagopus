@extends('layouts/client')
@section('title', __('calagopus::client.backups.title'))
@section('content')
    <div class="max-w-[85rem] px-4 py-10 sm:px-6 lg:px-8 lg:py-14 mx-auto">
        @include('shared/alerts')

        <div class="card">
            <div class="p-4 md:p-5">
                <h1 class="text-xl font-semibold text-gray-800 dark:text-gray-200">{{ __('calagopus::client.backups.title') }}</h1>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">{{ __('calagopus::client.backups.intro') }}</p>
                <p class="mt-3 border-l-2 border-gray-300 pl-3 text-sm text-gray-600 dark:border-gray-700 dark:text-gray-400">{{ __('calagopus::client.backups.scope_note') }}</p>
            </div>
        </div>

        @if ($entries->isEmpty())
            <div class="card mt-6">
                <div class="flex flex-auto flex-col justify-center items-center p-4 md:p-5">
                    <i class="bi bi-archive text-4xl text-gray-800 dark:text-gray-200" aria-hidden="true"></i>
                    <p class="mt-5 text-sm text-gray-800 dark:text-gray-400">{{ __('calagopus::client.backups.empty') }}</p>
                </div>
            </div>
        @else
            @foreach ($entries->groupBy('service_id') as $serviceId => $group)
                @php($service = $group->first()->service)
                <section class="card mt-6" aria-labelledby="backup-group-{{ $serviceId }}">
                    <div class="p-4 md:p-5">
                        <h2 id="backup-group-{{ $serviceId }}" class="text-lg font-medium text-gray-800 dark:text-gray-200">
                            {{ $service?->name ?? __('calagopus::client.backups.unknown_service') }}
                        </h2>

                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full text-sm">
                                <caption class="sr-only">{{ __('calagopus::client.backups.table_caption') }}</caption>
                                <thead>
                                    <tr class="text-left text-gray-600 dark:text-gray-400">
                                        <th scope="col" class="py-2 pr-4 font-medium">{{ __('calagopus::client.backups.name') }}</th>
                                        <th scope="col" class="py-2 pr-4 font-medium">{{ __('calagopus::client.backups.purge_at') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="text-gray-800 dark:text-gray-200">
                                    @foreach ($group as $entry)
                                        <tr class="border-t border-gray-200 dark:border-gray-800">
                                            <td class="py-2 pr-4">{{ $entry->backup_name ?? __('calagopus::client.backups.unnamed') }}</td>
                                            <td class="py-2 pr-4">{{ $entry->purge_at?->translatedFormat('d F Y') ?? __('calagopus::client.backups.no_limit') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <details class="mt-4">
                            <summary class="cursor-pointer text-sm font-semibold text-red-700 dark:text-red-400">
                                {{ trans_choice('calagopus::client.backups.delete_open', $group->count(), ['count' => $group->count()]) }}
                            </summary>
                            <div class="mt-3 rounded-xl border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                                <p class="text-sm text-red-900 dark:text-red-100">{{ __('calagopus::client.backups.delete_warning') }}</p>
                                <form method="POST" action="{{ route('calagopus.backups.destroy') }}" class="mt-3">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="service" value="{{ $serviceId }}">
                                    <button type="submit" class="inline-flex items-center gap-x-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-semibold text-white hover:bg-red-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-red-700">
                                        {{ __('calagopus::client.backups.delete_confirm') }}
                                    </button>
                                </form>
                            </div>
                        </details>
                    </div>
                </section>
            @endforeach
        @endif
    </div>
@endsection
