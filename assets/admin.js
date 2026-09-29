/*
 * Back-office only (loaded by DashboardController::configureAssets()).
 *
 * Autocompletes flagged by CreatableAutocompleteExtension let the user create the entry
 * they typed. New entries are submitted as "__new__:<name>" so the server cannot mistake
 * them for an id — a band called "999" exists.
 */
document.addEventListener('ea.autocomplete.pre-connect', (event) => {
    if ('true' !== event.target.dataset.kbrAutocompleteCreate) {
        return;
    }

    event.detail.config.create = (input) => ({ entityId: `__new__:${input}`, entityAsString: input });
});
