/*
  Requirement: Populate the "Weekly Course Breakdown" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="week-list-section"` to the
     <section> element that will contain the weekly articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the week list ('#week-list-section').
const listSection = document.getElementById('week-list-section');
// --- Functions ---

/**
 * TODO: Implement the createWeekArticle function.
 * It takes one week object {id, title, startDate, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * - The "View Details & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which week to load).
 */
function createWeekArticle(week) {
  // ... your implementation here ...
  const weekId = week.id;
  const weekTitle = week.title;
  const weekStartDate = week.start_date;
  const weekDescription = week.description;
  const article = document.createElement('article');
  const h2 = document.createElement('h2');
  h2.textContent = weekTitle;
  const p1= document.createElement('p');
  p1.textContent = `Start Date: ${weekStartDate}`;
  const p2 = document.createElement('p');
  p2.textContent = weekDescription;
  const a = document.createElement('a');
  a.href = `details.html?id=${weekId}`;
  a.textContent = 'View Details & Discussion';
  article.appendChild(h2);
  article.appendChild(p1);
  article.appendChild(p2);
  article.appendChild(a);
  return article;
}

/**
 * TODO: Implement the loadWeeks function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the weeks array. For each week:
 * - Call `createWeekArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadWeeks() {
  // ... your implementation here ...
  try{
    const response = await fetch("./api/?resource=weeks");
    const result = await response.json();
    const weeks = result.data || [];
    listSection.innerHTML = '';
    weeks.forEach(week => {
      const article = createWeekArticle(week);
      listSection.appendChild(article);
    });
  }catch(error){
    console.log("Error loading weeks:", error);
    listSection.innerHTML = '<p>Error loading weeks data.</p>';
  }
}

// --- Initial Page Load ---
// Call the function to populate the page.
loadWeeks();
