/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
const resourceListSection = document.getElementById("resource-list-section");

// --- Functions ---

/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
  // ... your implementation here ...
   const article = document.createElement("article");
    article.classList.add("resource-item");

    article.innerHTML = `
        <h3>${resource.title}</h3>
        <p>${resource.description}</p>
        <a href="details.html?id=${resource.id}">
            View Resource & Discussion
        </a>
    `;

    return article;
}

/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadResources() {
  // ... your implementation here ...
try {
        // 1. Fetch resources.json
const response = await fetch("resources.json");

        // 2. Parse JSON
        const resources = await response.json();

        // 3. Clear previous content
        resourceListSection.innerHTML = "";

        // 4. Create articles for each resource
        resources.forEach(resource => {
            const articleEl = createResourceArticle(resource);
            resourceListSection.appendChild(articleEl);
        });

    } catch (error) {
        console.error("Error loading resources:", error);
        resourceListSection.innerHTML = "<p>Failed to load resources.</p>";
    }
}
// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
