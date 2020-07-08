
<?php

// Use in the “Post-Receive URLs” section of your GitHub repo.

echo getcwd()


if ( $_POST['payload'] ) {
shell_exec( ‘git reset --hard HEAD && git pull’ );
}


?>
