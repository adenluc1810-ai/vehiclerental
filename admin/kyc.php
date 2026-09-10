<?php
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');
$pageTitle = 'KYC Verification';

if (isset($_GET['action'], $_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'] === 'verify' ? 'verified' : ($_GET['action'] === 'reject' ? 'rejected' : null);
    if ($action) {
        $stmt = $pdo->prepare("SELECT * FROM kyc_documents WHERE id = ?");
        $stmt->execute([$id]);
        $doc = $stmt->fetch();
        if ($doc) {
            $pdo->prepare("UPDATE kyc_documents SET status = ? WHERE id = ?")->execute([$action, $id]);
            notify($doc['user_id'], 'KYC Update', "Your {$doc['doc_type']} document has been $action.");
            flash('success', "Document $action.");
        }
    }
    redirect('admin/kyc.php');
}

$stmt = $pdo->query("SELECT k.*, u.name, u.email FROM kyc_documents k JOIN users u ON k.user_id = u.id ORDER BY k.uploaded_at DESC");
$docs = $stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<h2>KYC Document Verification</h2>
<table>
<tr><th>User</th><th>Doc Type</th><th>File</th><th>Status</th><th>Uploaded</th><th>Actions</th></tr>
<?php foreach ($docs as $d): ?>
<tr>
    <td><?php echo e($d['name']); ?> (<?php echo e($d['email']); ?>)</td>
    <td><?php echo e($d['doc_type']); ?></td>
    <td><a href="../uploads/kyc/<?php echo e($d['file_path']); ?>" target="_blank">View File</a></td>
    <td><span class="status-pill status-<?php echo $d['status']; ?>"><?php echo ucfirst($d['status']); ?></span></td>
    <td><?php echo date('M j, Y', strtotime($d['uploaded_at'])); ?></td>
    <td class="flex">
        <?php if ($d['status'] === 'pending'): ?>
        <a class="btn btn-sm btn-success" href="?action=verify&id=<?php echo $d['id']; ?>">Verify</a>
        <a class="btn btn-sm btn-danger" href="?action=reject&id=<?php echo $d['id']; ?>">Reject</a>
        <?php endif; ?>
    </td>
</tr>
<?php endforeach; ?>
<?php if (!$docs): ?><tr><td colspan="6" class="muted">No documents uploaded.</td></tr><?php endif; ?>
</table>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
