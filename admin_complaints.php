<?php
session_start();
include('db.php');

// 🎯 安全檢查與最高權限攔截（必須在 HTML 輸出前執行）
if (!isset($_SESSION['role_id']) || $_SESSION['role_id'] != 1) { 
    header("Location: login.php"); 
    exit(); 
}

// 🎯 處理管理員點擊不同按鈕的 POST 請求
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $usreport_id = intval($_POST['usreport_id']);
    $action = $_POST['action'];

    if ($action === 'resolve_only') {
        // A. 單純標示為已處理
        $stmt = $conn->prepare("UPDATE userreports SET status = 1 WHERE usreport_id = ?");
        $stmt->bind_param("i", $usreport_id);
        $stmt->execute();
    } 
    elseif ($action === 'block_user') {
        // B. 停權封鎖用戶：先去撈出被檢舉人的 u_id，然後將其 is_blocked 改為 1
        $target_uid = intval($_POST['target_uid']);
        
        // 1. 封鎖該用戶
        $stmt_block = $conn->prepare("UPDATE accounts SET is_blocked = 1 WHERE u_id = ?");
        $stmt_block->bind_param("i", $target_uid);
        $stmt_block->execute();
        $stmt_block->close();
        
        // 2. 將這則檢舉案件同步標示為已處理（結案）
        $stmt = $conn->prepare("UPDATE userreports SET status = 1 WHERE usreport_id = ?");
        $stmt->bind_param("i", $usreport_id);
        $stmt->execute();
    }
    elseif ($action === 'warn_user') {
        // 🎯 C. 發出警告：將被檢舉人的 has_warning 欄位設為 1
        $target_uid = intval($_POST['target_uid']);
        
        // 1. 將資料庫中該用戶的未讀警告狀態打開
        $stmt_warn = $conn->prepare("UPDATE accounts SET has_warning = 1 WHERE u_id = ?");
        $stmt_warn->bind_param("i", $target_uid);
        $stmt_warn->execute();
        $stmt_warn->close();
        
        // 2. 將這則檢舉案件同步標示為已處理（結案）
        $stmt = $conn->prepare("UPDATE userreports SET status = 1 WHERE usreport_id = ?");
        $stmt->bind_param("i", $usreport_id);
        $stmt->execute();
        
        // 使用 Session 暫存訊息，等一下重新整理後彈出警告提示
        $_SESSION['flash_msg'] = "已成功對該用戶發出違規警告！用戶下次登入將收到通知。";
    }
    
    header("Location: admin_complaints.php"); 
    exit();
}

include('header.php');

// 撈取檢舉留言
$comment_complaints = [];
try {
    $sql = "SELECT c.usreport_id, c.com_id, c.u_id, c.reason, c.other_reason_text, c.created_at,
                   a1.name AS reporter_name, a2.name AS target_user_name, cm.content AS bad_comment,
                   cm.u_id AS target_uid
            FROM userreports c 
            JOIN accounts a1 ON c.u_id = a1.u_id
            JOIN comments cm ON c.com_id = cm.com_id
            JOIN accounts a2 ON cm.u_id = a2.u_id
            WHERE c.status = 0 
            ORDER BY c.created_at DESC";
    $res = $conn->query($sql);
    if($res) { while ($row = $res->fetch_assoc()) $comment_complaints[] = $row; }
} catch (Exception $e) {}

$user_complaints = [];
?>
<style>
    .page-content { padding: 20px; padding-bottom: 80px; }
    .single-title-container { background: white; border-bottom: 1px solid #ddd; position: sticky; top: 52px; z-index: 100; text-align: center; padding: 15px 0; color: #E53935; font-weight: bold; font-size: 16px; letter-spacing: 1px; }

    /* 卡片與原有設計完全一致 */
    .comp-card { background: white; border-left: 5px solid #FF8C42; padding: 15px; border-radius: 8px; margin-bottom: 15px; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
    .comp-title { font-weight: bold; color: #333; font-size: 15px; margin-bottom: 8px; display: flex; justify-content: space-between;}
    .comp-target { background: #f4f6f8; padding: 10px; border-radius: 8px; font-size: 13px; color: #555; margin-bottom: 10px; border: 1px dashed #ccc; }
    
    /* 按鈕排版 */
    .admin-action-row { display: flex; gap: 8px; margin-top: 10px; }
    .admin-action-row .btn-action { flex: 1; border: none; padding: 10px 5px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 12px; color: white; display: flex; align-items: center; justify-content: center; gap: 3px; }
    
    .btn-lvl-resolve { background: #34C759; } 
    .btn-lvl-warn { background: #FF9800; }    
    .btn-lvl-block { background: #E53935; }   

    .custom-confirm-overlay { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 99999; }
    .custom-confirm-box { background: white; padding: 22px; border-radius: 15px; width: 80%; max-width: 300px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); text-align: center; }
    .custom-confirm-box p { margin: 0 0 20px; font-size: 15px; color: #333; font-weight: bold; line-height: 1.4; }
    .custom-confirm-btns { display: flex; gap: 12px; justify-content: center; }
    .custom-btn { flex: 1; border: none; padding: 10px; border-radius: 20px; font-weight: bold; font-size: 14px; cursor: pointer; }
    .custom-btn-cancel { background: #eee; color: #555; }
    .custom-btn-confirm { background: #E53935; color: white; }
</style>

<div class="mobile-wrapper">
    <div class="header-blue" style="display: flex; align-items: center; gap: 15px;">
        <a href="admin_dashboard.php" style="color: white; text-decoration: none; font-size: 22px;">❮</a>
        <h1 style="margin: 0; font-size: 20px;">檢舉審核管理</h1>
    </div>

    <div class="single-title-container">
        待處理檢舉項目 (<?php echo count($comment_complaints); ?>)
    </div>

    <div class="page-content">
        <?php if(count($comment_complaints) > 0): ?>
            <?php foreach($comment_complaints as $c): 
                $reason_text = "";
                switch ($c['reason']) {
                    case 1: $reason_text = "不雅用語"; break;
                    case 2: $reason_text = "不雅照片"; break;
                    case 3: $reason_text = "偏離主題"; break;
                    case 4: $reason_text = "垃圾內容"; break;
                    case 5: $reason_text = "歧視或仇恨言論"; break;
                    case 6: $reason_text = "內容有害"; break;
                    case 7: $reason_text = "其他原因：" . $c['other_reason_text']; break;
                    default: $reason_text = "未知原因"; break;
                }
            ?>
                <div class="comp-card">
                    <div class="comp-title">
                        <span>檢舉理由：<?php echo htmlspecialchars($reason_text); ?></span>
                        <span style="font-size: 12px; color: #999; font-weight: normal;"><?php echo date('m/d H:i', strtotime($c['created_at'])); ?></span>
                    </div>
                    <div class="comp-target">
                        <span style="color: #E53935; font-weight: bold;">[被檢舉留言]</span> <?php echo htmlspecialchars($c['target_user_name']); ?>：<br>
                        "<?php echo htmlspecialchars($c['bad_comment']); ?>"
                    </div>
                    <div style="font-size: 12px; color: #888; margin-bottom: 10px;">檢舉人：<?php echo htmlspecialchars($c['reporter_name']); ?> (編號: <?php echo $c['u_id']; ?>)</div>
                    
                    <form method="POST" id="form-resolve-comment-<?php echo $c['usreport_id']; ?>">
                        <input type="hidden" name="usreport_id" value="<?php echo $c['usreport_id']; ?>">
                        <input type="hidden" name="target_uid" value="<?php echo $c['target_uid']; ?>">
                        <input type="hidden" name="action" id="action-field-<?php echo $c['usreport_id']; ?>" value="resolve_only">
                        
                        <div class="admin-action-row">
                            <button type="button" class="btn-action btn-lvl-resolve" onclick="openConfirmModal(<?php echo $c['usreport_id']; ?>, 'resolve_only', '確定要將此案件標示為已讀並結案嗎？')">
                                ✓ 僅結案
                            </button>
                            <button type="button" class="btn-action btn-lvl-warn" onclick="openConfirmModal(<?php echo $c['usreport_id']; ?>, 'warn_user', '確定要對該用戶發出違規警告並結案嗎？')">
                                ⚠️ 警告
                            </button>
                            <button type="button" class="btn-action btn-lvl-block" onclick="openConfirmModal(<?php echo $c['usreport_id']; ?>, 'block_user', '警告：確定要直接停權封鎖該違規用戶嗎？')">
                                封鎖
                            </button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align: center; color: #999; margin-top: 40px;">目前沒有被檢舉的留言</div>
        <?php endif; ?>
    </div>
</div>

<div id="customConfirmModal" class="custom-confirm-overlay">
    <div class="custom-confirm-box">
        <p id="confirmModalText">確定要將此檢舉案件<br>標示為已處理嗎？</p>
        <div class="custom-confirm-btns">
            <button type="button" class="custom-btn custom-btn-cancel" onclick="closeConfirmModal()">取消</button>
            <button type="button" class="custom-btn custom-btn-confirm" id="modalConfirmBtn">確定</button>
        </div>
    </div>
</div>

<script>
    let currentActiveReportId = null;

    function openConfirmModal(usreportId, actionType, alertText) {
        currentActiveReportId = usreportId;
        const actionField = document.getElementById('action-field-' + usreportId);
        if (actionField) {
            actionField.value = actionType;
        }
        document.getElementById('confirmModalText').innerHTML = alertText;
        document.getElementById('customConfirmModal').style.display = 'flex';
    }

    function closeConfirmModal() {
        document.getElementById('customConfirmModal').style.display = 'none';
        currentActiveReportId = null;
    }

    document.getElementById('modalConfirmBtn').addEventListener('click', function() {
        if (currentActiveReportId) {
            const targetForm = document.getElementById('form-resolve-comment-' + currentActiveReportId);
            if (targetForm) {
                targetForm.submit();
            }
        }
    });

    <?php if (isset($_SESSION['flash_msg'])): ?>
        alert("<?php echo $_SESSION['flash_msg']; ?>");
        <?php unset($_SESSION['flash_msg']); ?>
    <?php endif; ?>
</script>

<?php include('footer.php'); ?>