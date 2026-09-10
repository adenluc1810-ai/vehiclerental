<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('customer');
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM bookings WHERE id = ? AND customer_id = ? AND status = 'completed'");
$stmt->execute([$bookingId, $_SESSION['user_id']]);
$b = $stmt->fetch();
if (!$b) { flash('error', 'You can only review completed bookings.'); redirect('booking/my_bookings.php'); }

$stmt = $pdo->prepare("SELECT * FROM reviews WHERE booking_id = ?");
$stmt->execute([$bookingId]);
if ($stmt->fetch()) { flash('info', 'You already reviewed this booking.'); redirect('booking/my_bookings.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating = max(1, min(5, (int)$_POST['rating']));
    $comment = trim($_POST['comment']);
    $stmt = $pdo->prepare("INSERT INTO reviews (booking_id, customer_id, vehicle_id, rating, comment) VALUES (?,?,?,?,?)");
    $stmt->execute([$bookingId, $_SESSION['user_id'], $b['vehicle_id'], $rating, $comment]);
    flash('success', 'Thank you for your review!');
    redirect('booking/my_bookings.php');
}

$pageTitle = 'Leave a Review';
require_once __DIR__ . '/../includes/header.php';
?>
<div class="card" style="max-width:480px;margin:0 auto;">
    <h2>Rate Your Rental Experience</h2>
    <form method="post">
        <input type="hidden" name="booking_id" value="<?php echo $bookingId; ?>">
        <div class="form-group">
            <label>Rating</label>
            <select name="rating" required>
                <option value="5">★★★★★ Excellent</option>
                <option value="4">★★★★☆ Good</option>
                <option value="3">★★★☆☆ Average</option>
                <option value="2">★★☆☆☆ Poor</option>
                <option value="1">★☆☆☆☆ Terrible</option>
            </select>
        </div>
        <div class="form-group">
            <label>Comment</label>
            <textarea name="comment" rows="3"></textarea>
        </div>
        <button class="btn" type="submit">Submit Review</button>
    </form>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
