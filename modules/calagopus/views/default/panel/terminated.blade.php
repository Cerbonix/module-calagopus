<section class="card" aria-labelledby="calagopus-terminated-heading">
    <div class="p-4 md:p-5">
        <h2 id="calagopus-terminated-heading" class="text-lg font-medium text-gray-800 dark:text-gray-200">
            {{ __('calagopus::client.terminated.heading') }}
        </h2>

        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            {{ trans_choice('calagopus::client.terminated.kept', $count, ['count' => $count]) }}
            @if ($purgeAt)
                {{ __('calagopus::client.terminated.until', ['date' => $purgeAt->translatedFormat('d F Y')]) }}
            @else
                {{ __('calagopus::client.terminated.no_limit') }}
            @endif
        </p>

        <a href="{{ route('calagopus.backups.index') }}" class="mt-4 inline-flex items-center gap-x-2 rounded-lg bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-700 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-700">
            {{ __('calagopus::client.terminated.open') }}
        </a>
    </div>
</section>
