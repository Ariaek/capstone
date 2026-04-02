    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const body = document.body;

    function openModal(modal) {
        if (!modal) return;
        modal.classList.add('show');
        body.style.overflow = 'hidden';
    }

    function closeModal(modal) {
        if (!modal) return;
        modal.classList.remove('show');
        body.style.overflow = '';
    }

    document.querySelectorAll('[data-modal-target]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const selector = this.getAttribute('data-modal-target');
            const modal = document.querySelector(selector);
            openModal(modal);
        });
    });

    document.querySelectorAll('[data-close-modal]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            closeModal(this.closest('.modal'));
        });
    });

    document.querySelectorAll('.modal').forEach(function(modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(modal);
            }
        });
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.modal.show').forEach(function(modal) {
                closeModal(modal);
            });
        }
    });

    document.querySelectorAll('[data-export-table]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const selector = this.getAttribute('data-export-table');
            const table = document.querySelector(selector);
            if (!table) return alert('Table not found.');

            let csv = [];
            const rows = table.querySelectorAll('tr');

            rows.forEach(function(row) {
                let cols = row.querySelectorAll('th, td');
                let rowData = [];
                cols.forEach(function(col) {
                    let text = col.innerText.replace(/\n/g, ' ').replace(/"/g, '""').trim();
                    rowData.push('"' + text + '"');
                });
                csv.push(rowData.join(','));
            });

            const blob = new Blob([csv.join('\n')], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            const url = URL.createObjectURL(blob);

            link.setAttribute('href', url);
            link.setAttribute('download', 'export.csv');
            link.style.display = 'none';

            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    });

    document.querySelectorAll('[data-print-section]').forEach(function(btn) {
        btn.addEventListener('click', function() {
            const selector = this.getAttribute('data-print-section');
            const section = document.querySelector(selector);
            if (!section) return alert('Printable section not found.');

            const printWindow = window.open('', '', 'width=1000,height=700');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Print</title>
                    <style>
                        body{font-family:Arial,Helvetica,sans-serif;padding:20px;color:#111827;}
                        table{width:100%;border-collapse:collapse;}
                        th,td{border:1px solid #d1d5db;padding:10px;text-align:left;font-size:14px;}
                        th{background:#f3f4f6;}
                        .stat-card,.content-card,.table-card{
                            border:1px solid #d1d5db;
                            border-radius:12px;
                            padding:16px;
                            margin-bottom:16px;
                        }
                    </style>
                </head>
                <body>${section.innerHTML}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });
    });

    const attendanceSearch = document.getElementById('attendanceSearch');
    const attendanceTable = document.getElementById('attendanceTable');

    if (attendanceSearch && attendanceTable) {
        attendanceSearch.addEventListener('input', function() {
            const keyword = this.value.toLowerCase().trim();
            const rows = attendanceTable.querySelectorAll('tbody tr');

            rows.forEach(function(row) {
                const text = row.innerText.toLowerCase();
                row.style.display = text.includes(keyword) ? '' : 'none';
            });
        });
    }
});
</script>
</body>
</html>