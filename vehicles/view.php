<?php
require_once __DIR__ . '/../includes/functions.php';
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("SELECT v.*, u.name AS owner_name, u.phone AS owner_phone FROM vehicles v JOIN users u ON v.owner_id = u.id WHERE v.id = ?");
$stmt->execute([$id]);
$vehicle = $stmt->fetch();
if (!$vehicle) { flash('error', 'Vehicle not found.'); redirect('vehicles/search.php'); }

$stmt = $pdo->prepare("SELECT image_path FROM vehicle_images WHERE vehicle_id = ?");
$stmt->execute([$id]);
$images = $stmt->fetchAll();

// Reviews
$stmt = $pdo->prepare("SELECT r.*, u.name FROM reviews r JOIN users u ON r.customer_id = u.id WHERE vehicle_id = ? ORDER BY r.created_at DESC");
$stmt->execute([$id]);
$reviews = $stmt->fetchAll();
$avgRating = 0;
if ($reviews) { $avgRating = round(array_sum(array_column($reviews,'rating')) / count($reviews), 1); }

// Availability calendar - next 30 days
$stmt = $pdo->prepare("SELECT start_date, end_date FROM bookings WHERE vehicle_id = ? AND status IN ('pending','confirmed','ongoing')");
$stmt->execute([$id]);
$bookedRanges = $stmt->fetchAll();

function isDateBooked($date, $ranges) {
    foreach ($ranges as $r) {
        // end_date is the drop day, so the vehicle is free again from that date
        if ($date >= $r['start_date'] && $date < $r['end_date']) return true;
    }
    return false;
}

$pageTitle = $vehicle['brand'] . ' ' . $vehicle['model'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="grid grid-2">
    <div>
        <img src="<?php echo e(vehicleImageUrl($vehicle['image'], $vehicle['brand'] . ' ' . $vehicle['model'], 500, 300)); ?>" style="width:100%;border-radius:10px;">
        <?php if ($images): ?>
        <div class="grid grid-4" style="margin-top:10px;">
            <?php foreach ($images as $img): ?>
                <img src="<?php echo BASE_URL . 'uploads/vehicles/' . e($img['image_path']); ?>" style="width:100%;height:70px;object-fit:cover;border-radius:6px;">
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    <div>
        <h2><?php echo e($vehicle['brand'] . ' ' . $vehicle['model']); ?> (<?php echo e($vehicle['year']); ?>)</h2>
        <p><span class="tag"><?php echo e($vehicle['category']); ?></span>
           <span class="tag"><?php echo e($vehicle['transmission']); ?></span>
           <span class="tag"><?php echo e($vehicle['fuel_type']); ?></span>
           <span class="tag"><?php echo e($vehicle['seats']); ?> seats</span></p>
        <p class="price"><?php echo money($vehicle['price_per_day']); ?> / day</p>
        <p><strong>Location:</strong> <?php echo e($vehicle['location']); ?></p>
        <p><strong>Owner:</strong> <?php echo e($vehicle['owner_name']); ?></p>
        <p><?php echo nl2br(e($vehicle['description'])); ?></p>
        <p>⭐ <?php echo $avgRating ?: 'No reviews yet'; ?> (<?php echo count($reviews); ?> reviews)</p>
        <span class="status-pill status-<?php echo $vehicle['status']; ?>"><?php echo ucfirst($vehicle['status']); ?></span>

        <?php if ($vehicle['status'] === 'available'): ?>
        <div class="card" style="margin-top:16px;">
            <h3>Book This Vehicle</h3>
            <?php if (!isLoggedIn()): ?>
                <p class="muted">Please <a href="<?php echo BASE_URL; ?>login.php">login</a> as a customer to book.</p>
            <?php elseif ($_SESSION['role'] !== 'customer'): ?>
                <p class="muted">Only customer accounts can make bookings.</p>
            <?php else: ?>
                <form method="post" action="<?php echo BASE_URL; ?>booking/create.php">
                    <?php echo csrfField(); ?>
                    <input type="hidden" name="vehicle_id" value="<?php echo (int)$vehicle['id']; ?>">
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Pickup Date</label>
                            <input type="date" id="start_date" name="start_date" required
                                   min="<?php echo date('Y-m-d'); ?>"
                                   value="<?php echo e($_GET['start_date'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Drop Date</label>
                            <input type="date" id="end_date" name="end_date" required
                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                   value="<?php echo e($_GET['end_date'] ?? ''); ?>">
                        </div>
                    </div>
                    <div class="grid grid-2">
                        <div class="form-group">
                            <label>Pickup Location</label>
                            <input type="text" name="pickup_location" value="<?php echo e($vehicle['location']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Drop Location</label>
                            <input type="text" name="drop_location" value="<?php echo e($vehicle['location']); ?>" required>
                        </div>
                    </div>
                    <p class="muted" id="quote">Rate: <?php echo money($vehicle['price_per_day']); ?> per day. Choose your dates to see the total.</p>
                    <button class="btn" type="submit" id="reserveBtn">Reserve Now</button>
                </form>
                <script>
                (function () {
                    var rate  = <?php echo json_encode((float)$vehicle['price_per_day']); ?>;
                    var start = document.getElementById('start_date');
                    var end   = document.getElementById('end_date');
                    var quote = document.getElementById('quote');
                    var form  = start.form;
                    var busy  = <?php echo json_encode(array_map(function ($r) {
                        return ['s' => $r['start_date'], 'e' => $r['end_date']];
                    }, $bookedRanges)); ?>;

                    function overlaps(s, e) {
                        return busy.some(function (r) { return !(e <= r.s || s >= r.e); });
                    }
                    function update() {
                        if (!start.value || !end.value) return;
                        end.min = new Date(new Date(start.value).getTime() + 86400000)
                                    .toISOString().slice(0, 10);
                        if (end.value <= start.value) {
                            quote.textContent = 'Drop date must be after the pickup date.';
                            return;
                        }
                        if (overlaps(start.value, end.value)) {
                            quote.textContent = 'Those dates are already booked — please pick another range.';
                            return;
                        }
                        var days = Math.round(
                            (new Date(end.value) - new Date(start.value)) / 86400000);
                        quote.textContent = days + ' day(s) x ' + rate.toFixed(2)
                                          + ' = ' + (days * rate).toFixed(2) + ' total';
                    }
                    start.addEventListener('change', update);
                    end.addEventListener('change', update);
                    update();

                    // prevent a double click creating two reservations
                    form.addEventListener('submit', function () {
                        var b = document.getElementById('reserveBtn');
                        setTimeout(function () { b.disabled = true; b.textContent = 'Reserving...'; }, 0);
                    });
                })();
                </script>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="card">
    <h3>Availability Calendar (next 30 days)</h3>
    <div class="calendar-grid">
    <?php for ($i = 0; $i < 30; $i++):
        $d = date('Y-m-d', strtotime("+$i days"));
        $busy = isDateBooked($d, $bookedRanges);
    ?>
        <div class="calendar-day <?php echo $busy ? 'busy' : 'free'; ?>"><?php echo date('M j', strtotime($d)); ?><br><?php echo $busy ? 'Booked' : 'Free'; ?></div>
    <?php endfor; ?>
    </div>
</div>

<div class="card">
    <h3>Reviews</h3>
    <?php if (!$reviews): ?>
        <p class="muted">No reviews yet.</p>
    <?php endif; ?>
    <?php foreach ($reviews as $r): ?>
        <div style="border-bottom:1px solid #eee;padding:10px 0;">
            <strong><?php echo e($r['name']); ?></strong> - <span class="stars"><?php echo str_repeat('★', $r['rating']) . str_repeat('☆', 5-$r['rating']); ?></span>
            <p><?php echo e($r['comment']); ?></p>
        </div>
    <?php endforeach; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
