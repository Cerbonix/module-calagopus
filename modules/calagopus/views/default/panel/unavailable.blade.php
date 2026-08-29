<section aria-labelledby="calagopus-unavailable-heading">
    <h2 id="calagopus-unavailable-heading" class="sr-only">{{ __('calagopus::client.heading') }}</h2>

    <div role="status" class="flex items-start gap-3 rounded-xl border border-gray-300 bg-gray-50 p-4 text-gray-900 dark:border-gray-700 dark:bg-slate-900 dark:text-gray-100">
        <i class="bi bi-exclamation-circle mt-0.5" aria-hidden="true"></i>
        <p>{{ __('calagopus::client.unavailable.'.$reason) }}</p>
    </div>
</section>
