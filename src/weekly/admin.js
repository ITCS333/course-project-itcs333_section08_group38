/*
  Requirement: Make the "Manage Weekly Breakdown" page interactive.

  Instructions:
  1. Link this file to `admin.html` using:
     <script src="admin.js" defer></script>
  
  2. In `admin.html`, add an `id="weeks-tbody"` to the <tbody> element
     inside your `weeks-table`.
  
  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// This will hold the weekly data loaded from the JSON file.
let weeks = [];

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm = document.getElementById('week-form');
// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTableBody = document.getElementById('weeks-tbody');
// --- Functions ---

/**
 * TODO: Implement the createWeekRow function.
 * It takes one week object {id, title, description}.
 * It should return a <tr> element with the following <td>s:
 * 1. A <td> for the `title`.
 * 2. A <td> for the `description`.
 * 3. A <td> containing two buttons:
 * - An "Edit" button with class "edit-btn" and `data-id="${id}"`.
 * - A "Delete" button with class "delete-btn" and `data-id="${id}"`.
 */
function createWeekRow(week) {
  // ... your implementation here ...
  let tr = document.createElement('tr');
  let tdTitle = document.createElement('td');
  tdTitle.textContent = week.title;
  tr.appendChild(tdTitle);
  let tdDescription = document.createElement('td');
  tdDescription.textContent = week.description;
  tr.appendChild(tdDescription);
  let tdActions = document.createElement('td');
  let editButton = document.createElement('button');
  editButton.className = 'edit-btn';
  editButton.setAttribute('data-id', week.id);
  editButton.textContent = 'Edit';
  tdActions.appendChild(editButton);
  let deleteButton = document.createElement('button');
  deleteButton.className = 'delete-btn';
  deleteButton.setAttribute('data-id', week.id);
  deleteButton.textContent = 'Delete';
  tdActions.appendChild(deleteButton);
  tr.appendChild(tdActions);
  return tr;

}

/**
 * TODO: Implement the renderTable function.
 * It should:
 * 1. Clear the `weeksTableBody`.
 * 2. Loop through the global `weeks` array.
 * 3. For each week, call `createWeekRow()`, and
 * append the resulting <tr> to `weeksTableBody`.
 */
function renderTable() {
  // ... your implementation here ...
  weeksTableBody.innerHTML = '';
  weeks.forEach(week =>
  {
    let tr = createWeekRow(week);
    weeksTableBody.appendChild(tr);
  });
}

/**
 * TODO: Implement the handleAddWeek function.
 * This is the event handler for the form's 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the values from the title, start date, and description inputs.
 * 3. Get the value from the 'week-links' textarea. Split this value
 * by newlines (`\n`) to create an array of link strings.
 * 4. Create a new week object with a unique ID (e.g., `id: \`week_${Date.now()}\``).
 * 5. Add this new week object to the global `weeks` array (in-memory only).
 * 6. Call `renderTable()` to refresh the list.
 * 7. Reset the form.
 */
function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();
  const title = document.getElementById('week-title').value;
  const startDate = document.getElementById('week-start-date').value;
  const description = document.getElementById('week-description').value;
  const linksText = document.getElementById('week-links').value;
  const links = linksText.split('\n').map(link => link.trim()).filter(link => link !== '');
  const newWeek = {
    id: `week_${Date.now()}`,
    title: title,
    startDate: startDate,
    description: description,
    links: links
  };
  weeks.push(newWeek);
  renderTable();
  weekForm.reset();
}
/**
 * TODO: Implement the handleTableClick function.
 * This is an event listener on the `weeksTableBody` (for delegation).
 * It should:
 * 1. Check if the clicked element (`event.target`) has the class "delete-btn".
 * 2. If it does, get the `data-id` attribute from the button.
 * 3. Update the global `weeks` array by filtering out the week
 * with the matching ID (in-memory only).
 * 4. Call `renderTable()` to refresh the list.
 */
function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains('delete-btn')) {
    const idToDelete = event.target.dataset.id;
    weeks = weeks.filter(week => week.id !== idToDelete);
    renderTable();
  }
}

/**
 * TODO: Implement the loadAndInitialize function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'weeks.json'.
 * 2. Parse the JSON response and store the result in the global `weeks` array.
 * 3. Call `renderTable()` to populate the table for the first time.
 * 4. Add the 'submit' event listener to `weekForm` (calls `handleAddWeek`).
 * 5. Add the 'click' event listener to `weeksTableBody` (calls `handleTableClick`).
 */
async function loadAndInitialize() {
  // ... your implementation here ...
  try{
  const response = await fetch('./api/weeks.json');
  weeks = await response.json();
  renderTable();
  weekForm.addEventListener('submit', handleAddWeek);
  weeksTableBody.addEventListener('click', handleTableClick);
  } catch (error) {
    console.log('Error loading weeks data:', error);
  }
}
// --- Initial Page Load ---
// Call the main async function to start the application.
loadAndInitialize();
