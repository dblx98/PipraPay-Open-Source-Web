<?php
    if (!defined('pp_allowed_access')) {
        die('Direct access not allowed');
    }

    // Handle cron job requests
    if(isset($_GET['cron'])){
        if (function_exists('pp_trigger_hook')) {
            pp_trigger_hook('pp_cron');
        }
        
        echo json_encode(['status' => "false", 'message' => "Direct access not allowed"]);
        exit();
    }

    // Handle default redirects (only if not a webhook or cron request)
    if(!isset($_GET['webhook']) && !isset($_GET['cron'])){
        if($global_user_login == true){
?>
            <script>
                location.href="https://<?php echo $_SERVER['HTTP_HOST']?>/admin/dashboard";
            </script>
<?php
        }else{
?>
            <script>
                location.href="https://<?php echo $_SERVER['HTTP_HOST']?>/admin/login";
            </script>
<?php
        }
    }
?>
