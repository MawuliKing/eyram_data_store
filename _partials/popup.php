<?php if (isset($_SESSION['popup_message'])): ?>
<style>
    .custom-popup {
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1055;
        background-color: <?php echo $_SESSION['popup_type'] === 'error' ? '#f8d7da' : '#d4edda'; ?>;
        color: <?php echo $_SESSION['popup_type'] === 'error' ? '#721c24' : '#155724'; ?>;
        padding: 20px 30px;
        border-radius: 8px;
        box-shadow: 0 0 10px rgba(0,0,0,0.3);
        font-weight: bold;
        animation: fadeInOut 3s ease-in-out forwards;
    }

    @keyframes fadeInOut {
        0% { opacity: 0; transform: translate(-50%, -60%); }
        10% { opacity: 1; transform: translate(-50%, -50%); }
        90% { opacity: 1; transform: translate(-50%, -50%); }
        100% { opacity: 0; transform: translate(-50%, -40%); }
    }
</style>

<div class="custom-popup">
    <?php echo $_SESSION['popup_message']; ?>
</div>
<?php unset($_SESSION['popup_message'], $_SESSION['popup_type']); ?>
<?php endif; ?>
