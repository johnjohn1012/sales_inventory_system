<?php
/**
 * Anti-Resubmission Handler
 * This file provides functions to prevent form resubmission on page refresh
 */

/**
 * Checks if the current request is a POST request
 * @return bool True if it's a POST request, false otherwise
 */
function isPostRequest() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Handles form submission and redirects to prevent resubmission
 * @param string $success_message Optional success message to display after redirect
 * @param string $error_message Optional error message to display after redirect
 * @return void
 */
function handleFormSubmission($success_message = null, $error_message = null) {
    if (isPostRequest()) {
        // Store messages in session if provided
        if ($success_message) {
            $_SESSION['success_message'] = $success_message;
        }
        if ($error_message) {
            $_SESSION['error_message'] = $error_message;
        }
        
        // Redirect to the same page to prevent form resubmission
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit();
    }
}

/**
 * Displays success message if exists in session
 * @return void
 */
function displaySuccessMessage() {
    if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php 
            echo $_SESSION['success_message'];
            unset($_SESSION['success_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
}

/**
 * Displays error message if exists in session
 * @return void
 */
function displayErrorMessage() {
    if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php 
            echo $_SESSION['error_message'];
            unset($_SESSION['error_message']);
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif;
}

/**
 * Adds JavaScript to prevent form resubmission on browser back/forward
 * @return void
 */
function addPreventResubmissionScript() {
    ?>
    <script>
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
    <?php
} 