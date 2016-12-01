<?php

/* █████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
 * █████████████████████████████████████████████████████HUMAN THEME FRAMEWORK██████████████████████████████████████████
 * ████████████████████████████████████████████████████████████<https://human.camp>████████████████████████████████████████████████████
 * ██████████████████████████████████████████████████        support@human.camp        █████████████████████████████████████████████
 * █████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
 * ████████████████████████████████████████████████████████████████                   ██████████████████████████████████████████████████████████████████
 * ███████████████████████████████████████████████      ██████████   ████████████    ████████       ████████████████████████████████████████████████████
 * █████████████████████████████████████████████      ███████████   ███      ███    ███████       ██████████████████████████████████████████████████████
 * ███████████████████████████████████████████      ██████████████     ███    ███████████       ████████████████████████████████████████████████████████
 * █████████████████████████████████████████      █████████████████████████████████████       ██████████████████████████████████████████████████████████
 * ████████████████████████████████████████                                                 ████████████████████████████████████████████████████████████
 * █████████████████████████████████████████               HUMAN               ████████████████████████████████████████████████████████████████
 * █████████████████████████████████████████                                       █████████████████████████████████████████████████████████████████████
 * █████████████████████████████████████████       █████████████████████       █████████████████████████████████████████████████████████████████████████
 * ████████████████████████████████████████      ██████████████████████      ███████████████████████████████████████████████████████████████████████████
 * ███████████████████████████████████████     ██████████████████████      █████████████████████████████████████████████████████████████████████████████
 * █████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
 * █████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████████
 *
 * @param Human Backup
 * @author SergeDirect <itpal24@gmail.com>
 *
 *
 */


$upload_dir = wp_upload_dir();
define('WPBACKUP_FILE_PERMISSION', 0755);
define('WPCLONE_ROOT', rtrim(str_replace("\\", "/", HUMAN_CHILD_PATH), "/\\") . '/');
define('WPCLONE_BACKUP_FOLDER', 'babies');
define('WPCLONE_DIR_UPLOADS', str_replace('\\', '/', $upload_dir['basedir']));
define('WPCLONE_DIR_BACKUP', HUMAN_CHILD_PATH . '/' . WPCLONE_BACKUP_FOLDER . '/');

define('WPCLONE_DIR_PLUGIN', str_replace('\\', '/', plugin_dir_path(__FILE__)));
define('WPCLONE_URL_PLUGIN', plugin_dir_url(__FILE__));
define('WPCLONE_INSTALLER_PATH', WPCLONE_DIR_PLUGIN);
define('WPCLONE_WP_CONTENT', str_replace('\\', '/', WP_CONTENT_DIR));

function wpCloneSafePathMode($path) {
            return str_replace("\\", "/", $path);
}

function wpCloneDirectory($path) {
            return rtrim(str_replace("//", "/", wpCloneSafePathMode($path)), '/') . '/';
}

function convertPathIntoUrl($path) {
            return str_replace(rtrim(WPCLONE_ROOT, "/\\"), site_url(), $path);
}

function convertUrlIntoPath($url) {
            return str_replace(site_url(), rtrim(WPCLONE_ROOT, "/\\"), $url);
}

function wpa_insert_data($name, $size) {
            global $wpdb;
            global $current_user;
            $wpdb->insert($wpdb->prefix . "wpclone", array(
                'backup_name' => $name,
                'data_time' => current_time('mysql', get_option('gmt_offset')),
                'creator' => $current_user->user_login,
                'backup_size' => $size)
            );

            $wpdb->flush();
}

function CreateWPFullBackupZip($backupName, $zipmode, $use_wpdb = false) {
            $folderToBeZipped = WPCLONE_DIR_BACKUP . $backupName;
            $destinationPath = $folderToBeZipped . '/' . basename(WPCLONE_WP_CONTENT);
            $zipFileName = WPCLONE_DIR_BACKUP . $backupName . '.zip';
            $exclude = wpa_excluded_dirs();
            $dbonly = false; // isset ( $_POST[ 'dbonly' ] ) && 'true' == $_POST[ 'dbonly' ] ? true : false;

            if (false === mkdir($folderToBeZipped))
                        wpa_backup_error('file', sprintf(__('Unable to create the temporary backup directory,please make sure that PHP has permission to write into the <code>%s</code> directory.'), WPCLONE_DIR_BACKUP));

            if (false === $dbonly)
                        wpa_copy_dir(untrailingslashit(WPCLONE_WP_CONTENT), $destinationPath, $exclude);

            wpa_save_prefix($folderToBeZipped);

            require HUMAN_FRIENDS_PATH . '/backup/f-heart/backup-db.php';
            $db_backup_path = $folderToBeZipped . '/wp-content/backup-db';
            backup_tables($db_backup_path);

            global $table_prefix;


            foreach (new DirectoryIterator($db_backup_path) as $file) {
                        if ($file->isDot())
                                    continue;
                        $db_str = $file->getPathname();

                        $db_replace = array(
                            $table_prefix,
                            home_url());
                        $db_replace_to = array(
                            'human_prefix',
                            'human_old_url');
                        $old_db_str = str_replace($table_prefix, 'human_prefix', $db_str);
                        $sql_contents = file_get_contents($db_str);
                        $db_file = fopen($old_db_str, 'w');
                        fwrite($db_file, str_replace($db_replace, $db_replace_to, $sql_contents));
                        fclose($db_file);
                        unlink($db_str);
            }
            /* error haldler is called from within the wpa_zip function */
            wpa_zip($zipFileName, $folderToBeZipped, $zipmode);
            $zipSize = filesize($zipFileName);
            wpa_delete_dir($folderToBeZipped);
            return array(
                $backupName . '.zip',
                $zipSize);
}

function bytesToSize($bytes, $precision = 2) {
            $kilobyte = 1024;
            $megabyte = $kilobyte * 1024;
            $gigabyte = $megabyte * 1024;
            $terabyte = $gigabyte * 1024;
            if (($bytes >= 0) && ($bytes < $kilobyte)) {
                        return $bytes . ' B';
            } elseif (($bytes >= $kilobyte) && ($bytes < $megabyte)) {
                        return round($bytes / $kilobyte, $precision) . ' KB';
            } elseif (($bytes >= $megabyte) && ($bytes < $gigabyte)) {
                        return round($bytes / $megabyte, $precision) . ' MB';
            } elseif (($bytes >= $gigabyte) && ($bytes < $terabyte)) {
                        return round($bytes / $gigabyte, $precision) . ' GB';
            } elseif ($bytes >= $terabyte) {
                        return round($bytes / $terabyte, $precision) . ' TB';
            } else {
                        return $bytes . ' B';
            }
}

function replaceSiteUrlFromDatabaseFile($databaseFile) {
            global $wp_filesystem;
            $fileContent = $wp_filesystem->get_contents($databaseFile);
            $pos = strpos($fileContent, 'siteurl') + 8;
            $urlStartPos = strpos($fileContent, '"', $pos) + 1;
            $urlEndPos = strpos($fileContent, '"', $urlStartPos);
            $backupSiteUrl = substr($fileContent, $urlStartPos, $urlEndPos - $urlStartPos);
            return $backupSiteUrl;
}

function processRestoringBackup($url, $zipmode) {
            wpa_cleanup(true);
            if (!is_string($url) || '' == $url) {
                        wpa_backup_error('restore', sprintf(__('The provided URL "<code>%s</code>" is either not valid or empty'), $url), true);
            }

            global $wp_filesystem;
            $temp_dir = trailingslashit(WPCLONE_WP_CONTENT) . 'wpclone-temp';
            $temp_dir_err = $wp_filesystem->mkdir($temp_dir);
            if (is_wp_error($temp_dir_err)) {
                        wpa_backup_error('dirrest', $temp_dir_err->get_error_message(), true);
            }
            $pathParts = pathinfo($url);
            $zipFilename = wpa_fetch_file($url);

            $result = wpa_unzip($zipFilename, $temp_dir, $zipmode);
            if ($result) {
                        $unzippedFolderPath = wpCloneSafePathMode(trailingslashit($temp_dir) . 'wpclone_backup');
                        if (!$wp_filesystem->is_dir($unzippedFolderPath)) {
                                    $unzippedFolderPath = wpCloneSafePathMode(trailingslashit($temp_dir) . $pathParts['filename']);
                        }

                        /* if we're here then the file extraction worked,but let's make doubly sure */
                        if (!$wp_filesystem->is_dir($unzippedFolderPath)) {
                                    wpa_backup_error('restore', sprintf(__('Cannot find <code>%s<code>'), $unzippedFolderPath), true);
                        }
                        /* check the table prefixes */
                        $old_db_prefix = $unzippedFolderPath . '/prefix.txt';
                        $prefix = wpa_check_prefix($old_db_prefix);
                        if ($prefix) {
                                    wpa_replace_prefix($prefix);
                        }
                        $wp_filesystem->delete($old_db_prefix);
                        /* import db */
                        $databaseFile = $unzippedFolderPath . '/database.sql';
                        $currentSiteUrl = processConfigAndDatabaseFile($databaseFile);
                        /*  */
                        $wp_filesystem->delete($databaseFile);

                        wpa_copy($unzippedFolderPath . '/wp-content', WPCLONE_WP_CONTENT);


                        $wp_filesystem->delete($temp_dir, true);
                        /* remove the zip file only if it was downloaded from an external location. */
                        $wptmp = explode('.', $zipFilename);
                        if (in_array('tmp', $wptmp)) {
                                    $wp_filesystem->delete($zipFilename);
                        }

                        echo "<h1>Restore Successful!</h1>";

                        echo "Visit your restored site [ <a href='{$currentSiteUrl}' target=blank>here</a> ]<br><br>";

                        echo "<strong>You may need to re-save your permalink structure <a href='{$currentSiteUrl}/wp-admin/options-permalink.php' target=blank>Here</a></strong>";
            } else {

                        echo "<h1>Restore unsuccessful!!!</h1>";

                        echo "Please try again.";
            }
}

function wpa_save_prefix($path) {
            global $wpdb;
            $prefix = $wpdb->prefix;
            $file = $path . '/prefix.txt';
            if (is_dir($path) && is_writable($path)) {
                        file_put_contents($file, $prefix);
            }
}

/**
 * Checks to see whether the destination site's table prefix matches that of the origin site.old prefix is returned in case of a mismatch.
 *
 * @param type $file path to the prefix.txt file.
 * @return type bool string
 */
function wpa_check_prefix($file) {
            global $wpdb;
            $prefix = $wpdb->prefix;
            if (file_exists($file) && is_readable($file)) {
                        $old_prefix = file_get_contents($file);
                        if ($prefix !== $old_prefix) {
                                    return $old_prefix;
                        } else {
                                    return false;
                        }
            }
            return false;
}

/**
 * @since 2.0.6
 *
 * @param type $zipfile path to the zip file that needs to be extracted.
 * @param type $path the place to where the file needs to be extracted.
 * @return as false in the event of failure.
 */
function wpa_unzip($zipfile, $path, $zipmode = false) {
            if ($zipmode) {

                        if (ini_get('mbstring.func_overload') && function_exists('mb_internal_encoding')) {
                                    $previous_encoding = mb_internal_encoding();
                                    mb_internal_encoding('ISO-8859-1');
                        }

                        define('PCLZIP_TEMPORARY_DIR', WPCLONE_DIR_BACKUP);
                        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php' );
                        $z = new PclZip($zipfile);
                        $files = $z->extract(PCLZIP_OPT_PATH, $path);

                        if (isset($previous_encoding))
                                    mb_internal_encoding($previous_encoding);

                        if ($files == 0) {
                                    wpa_backup_error('pclunzip', $z->errorInfo(true), true);
                        }
                        return true;
            } else {
                        $z = unzip_file($zipfile, $path);
                        if (is_wp_error($z)) {
                                    wpa_backup_error('unzip', $z->get_error_message(), true);
                        }
                        return true;
            }
}

/**
 * @since 2.0.6
 *
 * @param type $name name of the zip file.
 * @param type $file_list an array of files that needs to be archived.
 */
function wpa_zip($zip_name, $folder, $zipmode = false) {
            if ($zipmode || (!in_array('ZipArchive', get_declared_classes()) || !class_exists('ZipArchive'))) {
                        define('PCLZIP_TEMPORARY_DIR', WPCLONE_DIR_BACKUP);
                        require_once ( ABSPATH . 'wp-admin/includes/class-pclzip.php');
                        $z = new PclZip($zip_name);
                        $v_list = $z->create($folder, PCLZIP_OPT_REMOVE_PATH, WPCLONE_DIR_BACKUP);
                        if ($v_list == 0) {
                                    wpa_backup_error('pclzip', $z->errorInfo(true));
                        }
            } else {
                        $z = new ZipArchive();
                        if (true !== $z->open($zip_name, ZIPARCHIVE::CREATE)) {
                                    wpa_backup_error('zip', $z);
                        }
                        wpa_ziparc($z, $folder, WPCLONE_DIR_BACKUP);
                        $z->close();
            }
}

function wpa_ziparc($zip, $dir, $base) {
            $new_folder = str_replace($base, '', $dir);
            $zip->addEmptyDir($new_folder);
            foreach (glob($dir . '/*') as $file) {
                        if (is_dir($file)) {
                                    wpa_ziparc($zip, $file, $base);
                        } else {
                                    $new_file = str_replace($base, '', $file);
                                    $zip->addFile($file, $new_file);
                        }
            }
}

/**
 * just a simple function to increase PHP limits.
 * @since 2.0.6
 */
function wpa_bump_limits() {
            $time = isset($_POST['maxexec']) && '' != $_POST['maxexec'] ? $_POST['maxexec'] : 300; /* 300 seconds = 5 minutes */
            $mem = isset($_POST['maxmem']) && '' != $_POST['maxmem'] ? $_POST['maxmem'] . 'M' : '512M';
            @ini_set('memory_limit', $mem);
            @ini_set('max_execution_time', $time);
}

/**
 * @since 2.0.6
 */
function wpa_wpfs_init() {
            if (!empty($_REQUEST['del'])) {
                        wpa_remove_backup();
                        return true;
            }
            if (empty($_POST))
                        return false;
            check_admin_referer('wpclone-submit');

            wpa_bump_limits();

            if (isset($_POST['createBackup'])) {
                        wpa_create_backup();
                        return true;
            }

            $form_post = wp_nonce_url('admin.php?page=wp-clone', 'wpclone-submit');
            $extra_fields = array(
                'restore_from_url',
                'maxmem',
                'maxexec',
                'zipmode',
                'restoreBackup',
                'createBackup');
            $type = '';
            if (false === ($creds = request_filesystem_credentials($form_post, $type, false, false, $extra_fields))) {
                        return true;
            }
            if (!WP_Filesystem($creds)) {
                        request_filesystem_credentials($form_post, $type, true, false, $extra_fields);
                        return true;
            }

            $zipmode = isset($_POST['zipmode']) ? true : false;
            $url = isset($_POST['restoreBackup']) ? $_POST['restoreBackup'] : $_POST['restore_from_url'];
            processRestoringBackup($url, $zipmode);
            return true;
}

/**
 * @since 2.0.6
 */
function wpa_copy($source, $target) {
            echo '<br>Human copying files...';
            global $wp_filesystem;
            if (is_readable($source)) {
                        if (is_dir($source)) {
                                    if (!file_exists($target)) {
                                                $wp_filesystem->mkdir($target);

                                                echo '<br>Human New folder created ' . $target;
                                    } else {

                                    }
                                    $d = dir($source);
                                    if ($d->read !== false) {
                                                while (FALSE !== ($entry = $d->read())) {
                                                            if ($entry == '.' || $entry == '..') {
                                                                        continue;
                                                            }
                                                            $Entry = "{$source}/{$entry}";
                                                            if (is_dir($Entry)) {
                                                                        wpa_copy($Entry, $target . '/' . $entry);
                                                            } else {
                                                                        $wp_filesystem->copy($Entry, $target . '/' . $entry, true, FS_CHMOD_FILE);
                                                            }
                                                }
                                    } else {
                                                echo '<br>Error: the folder content unreadable ->' . $source;
                                    }
                                    $d->close();
                        } else {

                                    $wp_filesystem->copy($source, $target, true);
                        }
            } else {
                        echo '<br>Human Baby temporary source folder path doesn\'t exists';
            }
}

/**
 * @since 2.0.6
 */
function wpa_replace_prefix($newPrefix) {
            $wpconfig = wpa_wpconfig_path();
            global $wp_filesystem;

            if (!$wp_filesystem->is_writable($wpconfig)) {
                        if (false === $wp_filesystem->chmod($wpconfig))
                                    wpa_backup_error('wpconfig', sprintf(__("<code>%s</code> is not writable and wpclone was unable to change the file permissions."), $wpconfig), true);
            }

            $fileContent = $wp_filesystem->get_contents($wpconfig);
            $pos = strpos($fileContent, '$table_prefix');
            $str = substr($fileContent, $pos, strpos($fileContent, PHP_EOL, $pos) - $pos);
            $fileContent = str_replace($str, '$table_prefix = "' . $newPrefix . '";', $fileContent);
            $wp_filesystem->put_contents($wpconfig, $fileContent, 0600);
}

/**
 * @since 2.0.6
 */
function wpa_create_backup() {
            if (true === is_multisite())
                        die('wpclone does not work on multisite installs.');
            if (!file_exists(WPCLONE_DIR_BACKUP)) {
                        wpa_create_directory();
            }
            wpa_cleanup();
            $use_wpdb = isset($_POST['use_wpdb']) && 'true' == $_POST['use_wpdb'] ? true : false;
            $backupName = wpa_backup_name();

            $zipmode = isset($_POST['zipmode']) ? true : false;
            list($zipFileName, $zipSize) = CreateWPFullBackupZip($backupName, $zipmode, $use_wpdb);

            wpa_insert_data($zipFileName, $zipSize);
            $backZipPath = convertPathIntoUrl(WPCLONE_DIR_BACKUP . $zipFileName);
            $zipSize = bytesToSize($zipSize);
            echo <<<EOF

<h1>Backup Successful!</h1>

<br />

Here is your backup file : <br />

    <a href='{$backZipPath}'><span>{$backZipPath}</span></a> ( {$zipSize} ) &nbsp;&nbsp;|&nbsp;&nbsp;
    <input type='hidden' name='backupUrl' class='backupUrl' value="{$backZipPath}" />
    <a class='copy-button' href='#' data-clipboard-text='{$backZipPath}'>Copy URL</a> &nbsp;<br /><br />

    (Copy that link and paste it into the "Restore URL" of your new WordPress installation to clone this site)
EOF;
}

/**
 * @since 2.0.6
 */
function wpa_remove_backup() {
            check_admin_referer('wpclone-submit');
            $deleteRow = DeleteWPBackupZip($_REQUEST['del']);
            echo <<<EOT
        <h1>Deleted Successful!</h1> <br />

        {$deleteRow->backup_name} <br />

        File deleted from backup folder and database...
EOT;
}

/**
 * @since 2.1.2
 * copypasta from wp-load.php
 * @return the path to wp-config.php
 */
function wpa_wpconfig_path() {

            if (file_exists(ABSPATH . 'wp-config.php')) {

                        /** The config file resides in ABSPATH */
                        return ABSPATH . 'wp-config.php';
            } elseif (file_exists(dirname(ABSPATH) . '/wp-config.php') && !file_exists(dirname(ABSPATH) . '/wp-settings.php')) {

                        /** The config file resides one level above ABSPATH but is not part of another install */
                        return dirname(ABSPATH) . '/wp-config.php';
            } else {

                        return false;
            }
}

function wpa_fetch_file($path) {
            $z = pathinfo($path);
            global $wp_filesystem;
            if ($wp_filesystem->is_file(WPCLONE_DIR_BACKUP . $z['basename'])) {
                        return WPCLONE_DIR_BACKUP . $z['basename'];
            } else {
                        $url = download_url($path, 750);
                        if (is_wp_error($url))
                                    wpa_backup_error('url', $url->get_error_message(), true);
                        return $url;
            }
}

function wpa_backup_name() {
            $backup_name = 'wpclone_backup_' . date('dS_M_Y_h-iA') . '_' . get_option('blogname');
            $backup_name = substr(str_replace(' ', '', $backup_name), 0, 40);
            $rand_str = substr(str_shuffle("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789"), 0, 10);
            $backup_name = sanitize_file_name($backup_name) . '_' . $rand_str;
            return $backup_name;
}

function wpa_backup_error($error, $data, $restore = false) {

            $temp_dir = $restore ? trailingslashit(WPCLONE_WP_CONTENT) . 'wpclone-temp' : trailingslashit(WPCLONE_DIR_BACKUP) . 'wpclone_backup';

            if (!file_exists($temp_dir)) {
                        unset($temp_dir);
            }

            switch ($error) :
                        /* during backup */
                        case 'file' :
                                    $error = __('while copying files into the temp directory');
                                    break;
                        case 'db' :
                                    $error = __('during the database backup');
                                    break;
                        case 'zip' :
                                    $error = __('while creating the zip file using PHP\'s ZipArchive library');
                                    break;
                        case 'pclzip' :
                                    $error = __('while creating the zip file using the PclZip library');
                                    break;
                        /* during restore */
                        case 'dirrest' :
                                    $error = __('while creating the temp directory');
                                    break;
                        case 'filerest' :
                                    $error = __('while copying files from the temp directory into the wp-content directory');
                                    break;
                        case 'dbrest' :
                                    $error = __('while cloning the database');
                                    break;
                        case 'unzip' :
                                    $error = __('while extracting the zip file using WP\'s zip file extractor');
                                    break;
                        case 'pclunzip' :
                                    $error = __('while extracting the zip file using the PclZip library');
                                    break;
                        case 'url' :
                                    $error = __('while downloading the zip file');
                                    break;
                        case 'wpconfig' :
                                    $error = __('while trying to modify the table prefix in the wp-config.php file');
                                    break;
                        /* and a catch all for the things that aren't covered above */
                        default :
                                    $error = sprintf(__('during the %s process'), $error);
            endswitch;

            echo '<div class="wpclone_notice updated">';
            printf(__('The plugin encountered an error %s,the following error message was returned:</br>'), $error);
            echo '<div class="error">' . __('Error Message : ') . $data . '</div></br>';
            if (isset($temp_dir)) {
                        printf(__('Temporary files created in <code>%s</code> will be deleted.'), $temp_dir);
                        echo '</div>';
                        if ($restore) {
                                    global $wp_filesystem;
                                    $wp_filesystem->delete($temp_dir, true);
                        } else {
                                    wpa_delete_dir($temp_dir);
                        }
            } else {
                        echo '</div>';
            }
            die;
}

function wpa_cleanup($restore = false) {
            $backup_dir = $restore ? trailingslashit(WPCLONE_WP_CONTENT) . 'wpclone-temp' : trailingslashit(WPCLONE_DIR_BACKUP) . 'wpclone_backup';
            if (file_exists($backup_dir) && is_dir($backup_dir)) {
                        if ($restore) {
                                    global $wp_filesystem;
                                    $wp_filesystem->delete($backup_dir, true);
                        } else {
                                    wpa_delete_dir($backup_dir);
                        }
            }
}

/**
 * recursively copies a directory from one place to another. excludes 'uploads/wp-clone' by default.
 * @since 2.1.6
 * @param string $from
 * @param string $to
 * @param array $exclude an array of directory paths to exclude.
 */
function wpa_copy_dir($from, $to, $exclude) {
            if (false === stripos(wpCloneSafePathMode($from), rtrim(wpCloneSafePathMode(WPCLONE_DIR_BACKUP), "/\\"))) {
                        if (!file_exists($to))
                                    @mkdir($to);
                        $files = array_diff(scandir($from), array(
                            '.',
                            '..'));
                        foreach ($files as $file) {
                                    if (in_array($from . '/' . $file, $exclude)) {
                                                continue;
                                    } else {
                                                if (is_dir($from . '/' . $file)) {
                                                            wpa_copy_dir($from . '/' . $file, $to . '/' . $file, $exclude);
                                                } else {
                                                            @copy($from . '/' . $file, $to . '/' . $file);
                                                }
                                    }
                        }
                        unset($files);
            }
}

/**
 * recursively deletes all the files in the given directory.
 * @since 2.1.6
 * @param string $dir path to the directory that needs to be deleted.
 */
function wpa_delete_dir($dir) {
            if (!empty($dir)) {
                        $dir = trailingslashit($dir);
                        $files = array_diff(scandir($dir), array(
                            '.',
                            '..'));
                        foreach ($files as $file) {
                                    if (is_dir($dir . $file)) {
                                                wpa_delete_dir($dir . $file);
                                    } else {
                                                @unlink($dir . $file);
                                    }
                        }
                        @rmdir($dir);
            }
}

/**
 * @since 2.1.6
 */
function wpa_excluded_dirs() {
            $exclude = array();
            if (isset($_POST['exclude']) && '' != $_POST['exclude']) {
                        foreach (explode("\n", $_POST['exclude']) as $ex) {
                                    $ex = trim($ex);
                                    if ('' !== $ex) {
                                                $ex = trim($ex, "/\\");
                                                $exclude[] = trailingslashit(WPCLONE_WP_CONTENT) . str_replace('\\', '/', $ex);
                                    }
                        }
            }
            return $exclude;
}
