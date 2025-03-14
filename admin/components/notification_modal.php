<?php
function displayNotificationModal() {
?>
    <div class="modal fade" id="notificationModal" tabindex="-1" aria-labelledby="notificationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="notificationModalLabel">
                        <i class="fas fa-bell me-2"></i>
                        Notifications
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <!-- Notifications will be dynamically loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php
}
