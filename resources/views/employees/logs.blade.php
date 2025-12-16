<div class="modal fade" id="employeeLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Employee Logs</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="employeeLogsContainer">
                    <div class="table-responsive" style="max-height:60vh; overflow:auto;">
                        <table class="table table-striped mb-0" style="min-width:100%; table-layout:auto;">
                        <thead>
                            <tr>
                                <th>User</th>
                                <th>Action</th>
                                <th>Description</th>
                                <th>Time</th>
                            </tr>
                        </thead>
                        <tbody id="employeeLogsTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <style>
                /* Ensure long descriptions wrap and allow horizontal scrolling when necessary */
                #employeeLogsModal .table-responsive td,
                #employeeLogsModal .table-responsive th {
                    white-space: normal !important;
                    word-break: break-word !important;
                }
                /* Small padding to avoid content touching edge when scrolled */
                #employeeLogsModal .table-responsive {
                    padding-bottom: 8px;
                }
            </style>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
