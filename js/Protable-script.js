document.addEventListener('DOMContentLoaded', function() {
    const containers = document.querySelectorAll('.protable-container');
    
    containers.forEach(container => {
        const varName = container.getAttribute('data-var');
        const data = window[varName]; // Get the specific data for this table
        
        if (data && data.rows) {
            renderProTable(data.rows, container);
        }
    });
});

function renderProTable(rows, container) {
    let html = '<table>';
    rows.forEach((row, index) => {
        html += '<tr>';
        row.forEach(cell => {
            html += index === 0 ? `<th>${cell}</th>` : `<td>${cell}</td>`;
        });
        html += '</tr>';
    });
    html += '</table>';
    container.innerHTML = html;
}
