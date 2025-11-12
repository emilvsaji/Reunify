<?php
require_once 'config/config.php';

// Check if user is logged in and is admin or faculty
if (!isLoggedIn() || !in_array($_SESSION['user_role'], ['admin', 'faculty'])) {
    http_response_code(403);
    exit('Access denied');
}

$feedback_id = intval($_GET['id'] ?? 0);

if ($feedback_id === 0) {
    exit('Invalid feedback ID');
}

$db = getDB();
$query = "SELECT f.*, u.full_name, u.email, u.student_id 
          FROM feedback f
          JOIN users u ON f.student_id = u.user_id
          WHERE f.feedback_id = ?";

$stmt = $db->prepare($query);
$stmt->bind_param("i", $feedback_id);
$stmt->execute();
$result = $stmt->get_result();
$feedback = $result->fetch_assoc();
$stmt->close();

if (!$feedback) {
    exit('Feedback not found');
}

function getCategoryBadgeColor($category) {
    $colors = [
        'application' => '#3b82f6',
        'features' => '#8b5cf6',
        'performance' => '#06b6d4',
        'user_experience' => '#ec4899',
        'documentation' => '#14b8a6',
        'other' => '#6b7280'
    ];
    return $colors[$category] ?? '#6b7280';
}
?>

<style>
    :root {
        --primary: #2563eb;
        --secondary: #64748b;
        --success: #10b981;
        --warning: #f59e0b;
        --danger: #ef4444;
        --dark: #1e293b;
        --light-bg: #f8fafc;
        --white: #ffffff;
        --border: #e2e8f0;
    }

    .feedback-details {
        display: flex;
        flex-direction: column;
        gap: 1.5rem;
    }

    .detail-section {
        border-bottom: 1px solid var(--border);
        padding-bottom: 1rem;
    }

    .detail-section:last-child {
        border-bottom: none;
    }

    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .detail-row:last-child {
        margin-bottom: 0;
    }

    .detail-label {
        font-weight: 600;
        color: var(--secondary);
        font-size: 0.9rem;
        text-transform: uppercase;
    }

    .detail-value {
        color: var(--dark);
    }

    .badge {
        display: inline-block;
        padding: 0.35rem 0.75rem;
        border-radius: 999px;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .badge-success {
        background: rgba(16, 185, 129, 0.1);
        color: #059669;
    }

    .badge-warning {
        background: rgba(245, 158, 11, 0.1);
        color: #92400e;
    }

    .badge-info {
        background: rgba(59, 130, 246, 0.1);
        color: #1e40af;
    }

    .badge-danger {
        background: rgba(239, 68, 68, 0.1);
        color: #991b1b;
    }

    .badge-category {
        display: inline-block;
        padding: 0.4rem 1rem;
        border-radius: 4px;
        color: white;
        font-size: 0.85rem;
        font-weight: 500;
    }

    .rating-stars {
        color: #f59e0b;
        font-size: 1.2rem;
    }

    .feedback-text-box {
        background: var(--light-bg);
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 4px solid var(--primary);
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .response-box {
        background: rgba(16, 185, 129, 0.05);
        padding: 1rem;
        border-radius: 0.5rem;
        border-left: 4px solid #10b981;
        line-height: 1.6;
        white-space: pre-wrap;
        word-wrap: break-word;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-label {
        display: block;
        margin-bottom: 0.5rem;
        font-weight: 500;
        font-size: 0.9rem;
    }

    textarea, select {
        width: 100%;
        padding: 0.75rem;
        border: 1px solid var(--border);
        border-radius: 0.5rem;
        font-family: inherit;
        font-size: 1rem;
    }

    textarea:focus, select:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    textarea {
        min-height: 100px;
        resize: vertical;
    }

    .button-group {
        display: flex;
        gap: 1rem;
        justify-content: flex-end;
        margin-top: 1.5rem;
    }

    .btn {
        padding: 0.5rem 1rem;
        border: none;
        border-radius: 0.5rem;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.9rem;
    }

    .btn-primary {
        background: var(--primary);
        color: white;
    }

    .btn-primary:hover {
        background: #1d4ed8;
    }

    .btn-secondary {
        background: var(--secondary);
        color: white;
    }

    .btn-secondary:hover {
        background: #475569;
    }
</style>

<div class="feedback-details">
    <!-- Student Information -->
    <div class="detail-section">
        <h3 style="margin-bottom: 1rem; color: var(--dark);">Student Information</h3>
        <div class="detail-row">
            <div class="detail-label">Name</div>
            <div class="detail-value"><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'Anonymous' : $feedback['full_name']); ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Student ID</div>
            <div class="detail-value"><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'N/A' : ($feedback['student_id'] ?? 'N/A')); ?></div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Email</div>
            <div class="detail-value"><?php echo htmlspecialchars($feedback['is_anonymous'] ? 'N/A' : $feedback['email']); ?></div>
        </div>
    </div>

    <!-- Feedback Metadata -->
    <div class="detail-section">
        <h3 style="margin-bottom: 1rem; color: var(--dark);">Feedback Details</h3>
        <div class="detail-row">
            <div class="detail-label">Category</div>
            <div class="detail-value">
                <span class="badge-category" style="background-color: <?php echo getCategoryBadgeColor($feedback['category']); ?>;">
                    <?php echo ucfirst(str_replace('_', ' ', $feedback['category'])); ?>
                </span>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Rating</div>
            <div class="detail-value">
                <div class="rating-stars">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star" style="color: <?php echo $i <= $feedback['rating'] ? '#f59e0b' : '#e2e8f0'; ?>"></i>
                    <?php endfor; ?>
                    <span style="color: var(--dark); margin-left: 0.5rem; font-size: 1rem; font-weight: 500;">
                        <?php echo $feedback['rating']; ?>/5
                    </span>
                </div>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Status</div>
            <div class="detail-value">
                <?php 
                $status_badges = [
                    'new' => 'badge-danger',
                    'reviewed' => 'badge-info',
                    'in_progress' => 'badge-warning',
                    'resolved' => 'badge-success'
                ];
                $badge_class = $status_badges[$feedback['status']] ?? 'badge-secondary';
                ?>
                <span class="badge <?php echo $badge_class; ?>">
                    <?php echo ucfirst(str_replace('_', ' ', $feedback['status'])); ?>
                </span>
            </div>
        </div>
        <div class="detail-row">
            <div class="detail-label">Date</div>
            <div class="detail-value"><?php echo date('M d, Y \a\t h:i A', strtotime($feedback['created_at'])); ?></div>
        </div>
    </div>

    <!-- Feedback Content -->
    <div class="detail-section">
        <h3 style="margin-bottom: 1rem; color: var(--dark);">Feedback Content</h3>
        <div class="detail-row">
            <div class="detail-label">Subject</div>
            <div class="detail-value" style="font-weight: 500; font-size: 1.05rem;">
                <?php echo htmlspecialchars($feedback['subject']); ?>
            </div>
        </div>
        <div style="margin-bottom: 1rem;">
            <div class="detail-label" style="margin-bottom: 0.5rem;">Feedback Message</div>
            <div class="feedback-text-box">
                <?php echo htmlspecialchars($feedback['feedback_text']); ?>
            </div>
        </div>
    </div>

    <!-- Faculty Response Section -->
    <?php if ($feedback['faculty_response']): ?>
        <div class="detail-section">
            <h3 style="margin-bottom: 1rem; color: var(--success);"><i class="fas fa-check-circle"></i> Your Response</h3>
            <div class="detail-row">
                <div class="detail-label">Reviewed By</div>
                <div class="detail-value">You</div>
            </div>
            <div style="margin-bottom: 1rem;">
                <div class="detail-label" style="margin-bottom: 0.5rem;">Response</div>
                <div class="response-box">
                    <?php echo htmlspecialchars($feedback['faculty_response']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Admin Response (view only) -->
    <?php if ($feedback['admin_response']): ?>
        <div class="detail-section">
            <h3 style="margin-bottom: 1rem; color: var(--info);"><i class="fas fa-info-circle"></i> Admin Response</h3>
            <div style="margin-bottom: 1rem;">
                <div class="detail-label" style="margin-bottom: 0.5rem;">Admin Response</div>
                <div class="response-box" style="border-left-color: #3b82f6; background: rgba(59, 130, 246, 0.05);">
                    <?php echo htmlspecialchars($feedback['admin_response']); ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Response Form -->
    <div class="detail-section">
        <h3 style="margin-bottom: 1rem; color: var(--dark);">
            <i class="fas fa-reply"></i> Add Your Response
        </h3>
        <form onsubmit="return handleResponseSubmit(event, <?php echo $feedback['feedback_id']; ?>);">
            <div class="form-group">
                <label class="form-label">Update Status</label>
                <select id="statusSelect" required>
                    <option value="">Select Status...</option>
                    <option value="new" <?php echo ($feedback['status'] === 'new' ? 'selected' : ''); ?>>New</option>
                    <option value="reviewed" <?php echo ($feedback['status'] === 'reviewed' ? 'selected' : ''); ?>>Reviewed</option>
                    <option value="in_progress" <?php echo ($feedback['status'] === 'in_progress' ? 'selected' : ''); ?>>In Progress</option>
                    <option value="resolved" <?php echo ($feedback['status'] === 'resolved' ? 'selected' : ''); ?>>Resolved</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Your Response</label>
                <textarea id="responseText" placeholder="Write your response to this feedback..." required><?php echo htmlspecialchars($feedback['faculty_response'] ?? ''); ?></textarea>
            </div>
            <div class="button-group">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Close</button>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Submit Response
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function handleResponseSubmit(event, feedbackId) {
        event.preventDefault();
        
        const responseText = document.getElementById('responseText').value.trim();
        const status = document.getElementById('statusSelect').value;

        if (!responseText || !status) {
            alert('Please fill in both response and status');
            return false;
        }

        const formData = new FormData();
        formData.append('action', 'add_response');
        formData.append('feedback_id', feedbackId);
        formData.append('response_text', responseText);
        formData.append('status', status);

        fetch('feedback_analytics.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            alert('Response added successfully!');
            location.reload();
        })
        .catch(error => {
            alert('Error submitting response');
            console.error(error);
        });

        return false;
    }
</script>
