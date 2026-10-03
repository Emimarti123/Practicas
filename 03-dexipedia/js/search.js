
const form = document.querySelector(".search-form");

if (form) {
    const input = form.querySelector("input[name=q]");
    let timer;

    // Waits until the user stops typing for half a second before searching
    input.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(() => form.submit(), 500);
    });

    // Changing a filter searches right away
    form.querySelectorAll("select").forEach((select) => {
        select.addEventListener("change", () => form.submit());
    });

    // After the page reloads, the cursor goes back to the end of the search box
    if (new URLSearchParams(location.search).has("q")) {
        input.focus();
        input.setSelectionRange(input.value.length, input.value.length);
    }
}