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
let editingWeekId = null;

// --- Element Selections ---
// TODO: Select the week form ('#week-form').
const weekForm = document.getElementById('week-form');
// TODO: Select the weeks table body ('#weeks-tbody').
const weeksTableBody = document.getElementById('weeks-tbody');
const formHeading = document.querySelector('section h2');
const submitButton = document.getElementById('add-week');
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
async function handleAddWeek(event) {
  // ... your implementation here ...
  event.preventDefault();
  const title = document.getElementById('week-title').value;
  const startDate = document.getElementById('week-start-date').value;
  const description = document.getElementById('week-description').value;
  const linksText = document.getElementById('week-links').value;
  const links = linksText.split('\n').map(link => link.trim()).filter(link => link !== '');
  
  if (editingWeekId) {
    // Update existing week
    const weekData = {
      id: editingWeekId,
      title: title,
      start_date: startDate,
      description: description,
      links: links
    };
    
    try {
      const response = await fetch('./api/?resource=weeks', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(weekData)
      });
      const result = await response.json();
      if (result.success) {
        // Update the week in local array
        const index = weeks.findIndex(w => w.id == editingWeekId);
        if (index !== -1) {
          weeks[index] = { ...weeks[index], ...weekData };
        }
        renderTable();
        resetForm();
      } else {
        alert('Error updating week: ' + result.error);
      }
    } catch (error) {
      console.log('Error updating week:', error);
      alert('Error updating week');
    }
  } else {
    // Add new week
    const newWeek = {
      title: title,
      start_date: startDate,
      description: description,
      links: links
    };
    
    try {
      const response = await fetch('./api/?resource=weeks', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(newWeek)
      });
      const result = await response.json();
      if (result.success) {
        weeks.push(result.data);
        renderTable();
        weekForm.reset();
      } else {
        alert('Error adding week: ' + result.error);
      }
    } catch (error) {
      console.log('Error adding week:', error);
      alert('Error adding week');
    }
  }
}

function resetForm() {
  weekForm.reset();
  editingWeekId = null;
  formHeading.textContent = 'Add a New Week';
  submitButton.textContent = 'Add Week';
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
async function handleTableClick(event) {
  // ... your implementation here ...
  if (event.target.classList.contains('edit-btn')) {
    const idToEdit = event.target.dataset.id;
    const week = weeks.find(w => w.id == idToEdit);
    if (week) {
      editingWeekId = week.id;
      document.getElementById('week-title').value = week.title;
      document.getElementById('week-start-date').value = week.start_date;
      document.getElementById('week-description').value = week.description || '';
      document.getElementById('week-links').value = (week.links || []).join('\n');
      formHeading.textContent = 'Edit Week';
      submitButton.textContent = 'Update Week';
      weekForm.scrollIntoView({ behavior: 'smooth' });
      const updatedData = {
          week_id: idToEdit,
          title,
          start_date: startDate,
          description,
          links
        };
    }
  } else if (event.target.classList.contains('delete-btn')) {
    const idToDelete = event.target.dataset.id;
    
    if (!confirm('Are you sure you want to delete this week?')) {
      return;
    }
    
    try {
      const response = await fetch(`./api/?resource=weeks&id=${idToDelete}`, {
        method: 'DELETE'
      });
      const result = await response.json();
      if (result.success) {
        weeks = weeks.filter(week => week.id != idToDelete);
        renderTable();
        // If we were editing this week, reset the form
        if (editingWeekId == idToDelete) {
          resetForm();
        }
      } else {
        alert('Error deleting week: ' + result.error);
      }
    } catch (error) {
      console.log('Error deleting week:', error);
      alert('Error deleting week');
    }
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
  const response = await fetch('./api/?resource=weeks');
  const result = await response.json();
  weeks = result.data || [];
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
