<?php
require_once __DIR__ . '/../includes/session.php';
dcpStartSession();
require_once '../includes/auth.php';
requireAdmin();
require_once '../includes/db.php';
$pdo = getDB();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}$csrf = $_SESSION['csrf_token'];
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($csrf, $_POST['csrf_token'] ?? '')) {
        $error = 'Your form session expired. Reload and try again.';
    } else {
        $action = $_POST['action'] ?? '';
        $id = (int)($_POST['user_id'] ?? 0);
        $username = trim($_POST['username'] ?? '');
        $roleId = (int)($_POST['role_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $canAdd = ($_POST['can_add'] ?? '') === '1';
        $canEdit = ($_POST['can_edit'] ?? '') === '1';
        $canDelete = ($_POST['can_delete'] ?? '') === '1';
        if ($action === 'delete') {
            if ($id === (int)$_SESSION['user_id']) {
                $error = 'You cannot delete the account you are currently using.';
            } else {
                $target = $pdo->prepare('SELECT role_id, username FROM users WHERE user_id=?');
                $target->execute([$id]);
                $targetUser = $target->fetch();
                if (!$targetUser) {
                    $error = 'User not found.';
                } elseif ((int)$targetUser['role_id'] === 1 && (int)$pdo->query('SELECT COUNT(*) FROM users WHERE role_id=1')->fetchColumn() <= 1) {
                    $error = 'Keep at least one administrator account.';
                } elseif ((int)$pdo->query('SELECT COUNT(*) FROM activity_logs WHERE user_id='.(int)$id)->fetchColumn() > 0) {
                    $error = 'This user has activity history and cannot be deleted. Change the role instead.';
                } else {
                    $del = $pdo->prepare('DELETE FROM users WHERE user_id=?');
                    $del->execute([$id]);
                    logActivity("Deleted user account #$id ({$targetUser['username']}).");
                    flashSuccess('User deleted.');
                    header('Location: users.php');
                    exit;
                }
            }
        } elseif (($action === 'save') && $username !== '' && $roleId > 0) {
            try {
                $roleCheck = $pdo->prepare('SELECT COUNT(*) FROM roles WHERE role_id=?');
                $roleCheck->execute([$roleId]);
                if (!$roleCheck->fetchColumn()) {
                    $error = 'Choose a valid role.';
                } elseif (!$id && $password === '') {
                    $error = 'Enter a password for the new account.';
                } else {
                    if ($roleId === 1) {
                        $canAdd = true;
                        $canEdit = true;
                        $canDelete = false;
                    }

                    $pdo->beginTransaction();
                    try {
                        if ($id) {
                            if ($password !== '') {
                                $stmt = $pdo->prepare('UPDATE users SET username=?,role_id=?,password_hash=? WHERE user_id=?');
                                $stmt->execute([$username,$roleId,password_hash($password, PASSWORD_DEFAULT),$id]);
                            } else {
                                $stmt = $pdo->prepare('UPDATE users SET username=?,role_id=? WHERE user_id=?');
                                $stmt->execute([$username,$roleId,$id]);
                            }
                            $savedUserId = $id;
                        } else {
                            $stmt = $pdo->prepare('INSERT INTO users (username,password_hash,role_id) VALUES (?,?,?)');
                            $stmt->execute([$username,password_hash($password, PASSWORD_DEFAULT),$roleId]);
                            $savedUserId = (int)$pdo->lastInsertId('users_user_id_seq');
                        }

                        $permissionStmt = $pdo->prepare(
                            'INSERT INTO user_action_permissions (user_id, can_add, can_edit, can_delete)
                             VALUES (?, ?, ?, ?)
                             ON CONFLICT (user_id) DO UPDATE
                             SET can_add = EXCLUDED.can_add,
                                 can_edit = EXCLUDED.can_edit,
                                 can_delete = EXCLUDED.can_delete'
                        );
                        $permissionStmt->bindValue(1, $savedUserId, PDO::PARAM_INT);
                        $permissionStmt->bindValue(2, $canAdd, PDO::PARAM_BOOL);
                        $permissionStmt->bindValue(3, $canEdit, PDO::PARAM_BOOL);
                        $permissionStmt->bindValue(4, $canDelete, PDO::PARAM_BOOL);
                        $permissionStmt->execute();
                        $pdo->commit();
                    } catch (Throwable $exception) {
                        if ($pdo->inTransaction()) {
                            $pdo->rollBack();
                        }
                        throw $exception;
                    }

                    if ($id) {
                        logActivity("Updated user account #$id ($username).");
                        flashSuccess('User account and permissions updated.');
                    } else {
                        logActivity("Created user account #$savedUserId ($username).");
                        flashSuccess('User account created.');
                    }
                    header('Location: users.php');
                    exit;
                }
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Could not save the user. The username may already be in use.';
            }
        } else {
            $error = 'Enter a username and select a role.';
        }
    }
}
$roles = $pdo->query('SELECT role_id,role_name FROM roles ORDER BY role_id')->fetchAll();
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $q = $pdo->prepare(
        'SELECT u.user_id, u.username, u.role_id,
                CASE WHEN COALESCE(p.can_add, TRUE) THEN 1 ELSE 0 END AS can_add,
                CASE WHEN COALESCE(p.can_edit, TRUE) THEN 1 ELSE 0 END AS can_edit,
                CASE WHEN COALESCE(p.can_delete, FALSE) THEN 1 ELSE 0 END AS can_delete
         FROM users u
         LEFT JOIN user_action_permissions p ON p.user_id = u.user_id
         WHERE u.user_id = ?'
    );
    $q->execute([$editId]);
    $editing = $q->fetch();
    if (!$editing) {
        flashError('User not found.');
        header('Location: users.php');
        exit;
    }
}
$users = $pdo->query(
    'SELECT u.user_id, u.username, u.role_id, r.role_name,
            CASE WHEN COALESCE(p.can_add, TRUE) THEN 1 ELSE 0 END AS can_add,
            CASE WHEN COALESCE(p.can_edit, TRUE) THEN 1 ELSE 0 END AS can_edit,
            CASE WHEN COALESCE(p.can_delete, FALSE) THEN 1 ELSE 0 END AS can_delete
     FROM users u
     LEFT JOIN roles r ON r.role_id = u.role_id
     LEFT JOIN user_action_permissions p ON p.user_id = u.user_id
     ORDER BY u.user_id'
)->fetchAll();
$pageTitle = 'User management';
require_once '../includes/header.php';
?>
<?= displayFlash() ?>
<?php if ($error !== ''): ?>
    <div class="alert alert-danger" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<div class="dashboard-heading">
    <div><h1>User management</h1><p>Create and maintain accounts and roles.</p></div>
</div>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-semibold">
                <i class="bi bi-person-plus me-2" aria-hidden="true"></i><?= $editing ? 'Edit account' : 'Add an account' ?>
            </div>
            <div class="card-body">
                <form method="post" class="user-management-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="action" value="save">
                    <input type="hidden" name="user_id" value="<?= (int)($editing['user_id'] ?? 0) ?>">
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="username">Username <span class="text-danger">*</span></label>
                        <input class="form-control" id="username" name="username" maxlength="50" value="<?= htmlspecialchars($editing['username'] ?? '', ENT_QUOTES, 'UTF-8') ?>" autocomplete="username" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="role_id">Role <span class="text-danger">*</span></label>
                        <select class="form-select" id="role_id" name="role_id" required>
                            <option value="" disabled hidden <?= $editing ? '' : 'selected' ?>>Choose a role…</option>
                            <?php foreach ($roles as $role): ?>
                                <option value="<?= (int)$role['role_id'] ?>" <?= (($editing['role_id'] ?? '') == $role['role_id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($role['role_name'], ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <fieldset class="mb-3" id="userPermissions" <?= (int)($editing['role_id'] ?? 0) === 1 ? 'hidden' : '' ?>>
                        <legend class="form-label fw-semibold fs-6 mb-2">Record permissions</legend>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="can_add" name="can_add" value="1" <?= !$editing || (int)$editing['can_add'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="can_add">Add records</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="can_edit" name="can_edit" value="1" <?= !$editing || (int)$editing['can_edit'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="can_edit">Edit records</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="can_delete" name="can_delete" value="1" <?= $editing && (int)$editing['can_delete'] === 1 ? 'checked' : '' ?>>
                            <label class="form-check-label" for="can_delete">Delete records</label>
                        </div>
                        <div class="form-text">These permissions apply to farmers, farms, crops, plantings, and harvests. Viewing records is always allowed; administrators always have full access.</div>
                    </fieldset>
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="password"><?= $editing ? 'New password' : 'Password' ?><?= $editing ? ' (optional)' : ' *' ?></label>
                        <input class="form-control" id="password" name="password" type="password" <?= $editing ? '' : 'required' ?> autocomplete="new-password">
                        <?php if ($editing): ?><div class="form-text">Leave blank to keep the current password.</div><?php endif; ?>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-success" type="submit">
                            <i class="bi bi-check-lg me-1" aria-hidden="true"></i><?= $editing ? 'Save changes' : 'Create account' ?>
                        </button>
                        <?php if ($editing): ?><a href="users.php" class="btn btn-outline-secondary">Cancel</a><?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-semibold"><i class="bi bi-people me-2" aria-hidden="true"></i>Accounts</span>
                <span class="badge text-bg-light"><?= count($users) ?></span>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light"><tr><th>ID</th><th>Username</th><th>Role</th><th>Record permissions</th><th>Actions</th></tr></thead>
                    <tbody>
                    <?php if (!$users): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No user accounts found.</td></tr>
                    <?php else: foreach ($users as $user): ?>
                        <tr>
                            <td>#<?= (int)$user['user_id'] ?></td>
                            <td class="fw-semibold">
                                <?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>
                                <?= (int)$user['user_id'] === (int)$_SESSION['user_id'] ? ' <span class="badge text-bg-light">You</span>' : '' ?>
                            </td>
                            <td><?= htmlspecialchars($user['role_name'] ?? 'Unassigned', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if ((int)$user['role_id'] === 1): ?>
                                    <span class="badge text-bg-success">Full admin access</span>
                                <?php else: ?>
                                    <?php
                                    $enabledPermissions = [];
                                    foreach (['can_add' => 'Add', 'can_edit' => 'Edit', 'can_delete' => 'Delete'] as $permissionKey => $permissionLabel) {
                                        if ((int)$user[$permissionKey] === 1) {
                                            $enabledPermissions[] = $permissionLabel;
                                        }
                                    }
                                    ?>
                                    <?= $enabledPermissions ? htmlspecialchars(implode(' · ', $enabledPermissions), ENT_QUOTES, 'UTF-8') : 'View only' ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="d-flex flex-wrap gap-2">
                                    <a class="btn btn-sm btn-outline-primary" href="?edit=<?= (int)$user['user_id'] ?>">
                                        <i class="bi bi-pencil-square me-1" aria-hidden="true"></i>Edit
                                    </a>
                                    <?php if ((int)$user['user_id'] !== (int)$_SESSION['user_id']): ?>
                                        <button class="btn btn-sm btn-outline-danger" type="button" data-bs-toggle="modal" data-bs-target="#deleteUserModal" data-user-id="<?= (int)$user['user_id'] ?>" data-username="<?= htmlspecialchars($user['username'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="bi bi-trash me-1" aria-hidden="true"></i>Delete
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
        <div class="modal-header bg-danger text-white">
            <h2 class="modal-title fs-5" id="deleteUserTitle"><i class="bi bi-exclamation-triangle-fill me-2" aria-hidden="true"></i>Delete account</h2>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
            Permanently delete the account for <strong id="deleteUserName"></strong>?
            Accounts with activity history cannot be deleted. This action cannot be undone.
        </div>
        <div class="modal-footer">
            <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cancel</button>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="user_id" id="deleteUserId">
                <button class="btn btn-danger" type="submit"><i class="bi bi-trash me-1" aria-hidden="true"></i>Delete account</button>
            </form>
        </div>
    </div></div>
</div>
<script>
document.getElementById('role_id').addEventListener('change', function () {
    document.getElementById('userPermissions').hidden = this.value === '1';
});
document.getElementById('deleteUserModal').addEventListener('show.bs.modal', function (event) {
    var button = event.relatedTarget;
    document.getElementById('deleteUserName').textContent = button.dataset.username;
    document.getElementById('deleteUserId').value = button.dataset.userId;
});
</script>
<?php require_once '../includes/footer.php'; ?>
