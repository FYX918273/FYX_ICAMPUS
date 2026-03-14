<?php

require_once __DIR__ . '/functions.php';

function ensureUploadsDir(string $subDir): string
{
    $subDir = trim($subDir, "/ \t\n\r\0\x0B");
    if ($subDir === '') {
        $subDir = 'misc';
    }
    // 线上/前端 URL 用的是 /uploads/xxx，因此必须把文件存到“站点根目录”的 uploads 目录下。
    // 当前项目结构：<projectRoot>/includes/upload.php，所以 uploads 应该在 <projectRoot>/uploads
    $uploadsRoot = __DIR__ . '/../uploads';
    @mkdir($uploadsRoot, 0777, true);

    $target = rtrim($uploadsRoot, '/') . '/' . $subDir;
    @mkdir($target, 0777, true);
    return $target;
}

/**
 * @return array{ok:bool,path?:string,error?:string}
 */
function uploadImage(string $fieldName, string $subDir, int $maxBytes = 2097152): array
{
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return ['ok' => true];
    }

    $f = $_FILES[$fieldName];
    if (($f['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true];
    }
    if (($f['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => '上传失败，请重试。'];
    }
    if (($f['size'] ?? 0) > $maxBytes) {
        return ['ok' => false, 'error' => '图片过大，请选择小于 2MB 的图片。'];
    }

    $tmp = $f['tmp_name'] ?? '';
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        return ['ok' => false, 'error' => '上传文件无效。'];
    }

    $ext = '';
    $mime = '';
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi) {
            $mime = (string)finfo_file($fi, $tmp);
            finfo_close($fi);
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => '仅支持 jpg/png/gif/webp 图片。'];
    }
    $ext = $allowed[$mime];

    $dir = ensureUploadsDir($subDir);
    $name = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = rtrim($dir, '/') . '/' . $name;
    if (!move_uploaded_file($tmp, $dest)) {
        return ['ok' => false, 'error' => '保存图片失败，请检查 uploads 目录权限。'];
    }

    // 返回 Web 可访问的相对路径
    $webPath = '/uploads/' . trim($subDir, '/') . '/' . $name;
    return ['ok' => true, 'path' => $webPath];
}

/**
 * 根据存储在数据库中的 Web 路径（例如 /uploads/forum/xxx.jpg）删除磁盘文件。
 * 为了安全，只允许删除站点根目录 uploads 下的文件。
 */
function deleteUploadedFile(string $webPath): bool
{
    $webPath = trim($webPath);
    if ($webPath === '' || substr($webPath, 0, 9) !== '/uploads/') {
        return false;
    }

    $uploadsRoot = realpath(__DIR__ . '/../uploads');
    if ($uploadsRoot === false) {
        return false;
    }

    $relative = substr($webPath, strlen('/uploads/'));
    $relative = ltrim($relative, "/ \t\n\r\0\x0B");
    if ($relative === '' || strpos($relative, "\0") !== false) {
        return false;
    }

    $candidate = $uploadsRoot . '/' . $relative;
    $real = realpath($candidate);
    if ($real === false) {
        // 文件不存在也当作“已删除”
        return true;
    }
    $prefix = rtrim($uploadsRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
    if (substr($real, 0, strlen($prefix)) !== $prefix) {
        // 防止路径穿越到 uploads 之外
        return false;
    }

    if (!is_file($real)) {
        return true;
    }
    return @unlink($real);
}

/**
 * @param array<int,string> $webPaths
 */
function deleteUploadedFiles(array $webPaths): void
{
    foreach ($webPaths as $p) {
        if (is_string($p) && $p !== '') {
            deleteUploadedFile($p);
        }
    }
}

/**
 * 多图上传：支持 <input type="file" name="images[]" multiple>
 *
 * - $maxFiles: 最多上传张数（默认 9）
 * - $maxInputBytes: 单张原始文件的“硬上限”，超过直接拒绝（防止极端大文件占用资源）
 * - 会在服务器端按最长边 $maxPixels 缩放，并重新编码（jpg/webp/png）以降低体积与显示压力
 *
 * @return array{ok:bool,paths?:array<int,string>,error?:string}
 */
function uploadImages(
    string $fieldName,
    string $subDir,
    int $maxFiles = 9,
    int $maxInputBytes = 8388608,
    int $maxPixels = 1600
): array {
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName])) {
        return ['ok' => true, 'paths' => []];
    }

    $f = $_FILES[$fieldName];
    $names = $f['name'] ?? [];
    $types = $f['type'] ?? [];
    $tmpNames = $f['tmp_name'] ?? [];
    $errors = $f['error'] ?? [];
    $sizes = $f['size'] ?? [];

    if (!is_array($tmpNames)) {
        return ['ok' => true, 'paths' => []];
    }

    $count = count($tmpNames);
    $picked = [];
    for ($i = 0; $i < $count; $i++) {
        $err = $errors[$i] ?? UPLOAD_ERR_NO_FILE;
        if ($err === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        $picked[] = $i;
    }

    if (count($picked) === 0) {
        return ['ok' => true, 'paths' => []];
    }
    if (count($picked) > $maxFiles) {
        return ['ok' => false, 'error' => '最多只能上传 ' . $maxFiles . ' 张图片。'];
    }

    $dir = ensureUploadsDir($subDir);
    $paths = [];

    foreach ($picked as $idx => $i) {
        $err = $errors[$i] ?? UPLOAD_ERR_OK;
        if ($err !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => '第 ' . ($idx + 1) . ' 张图片上传失败，请重试。'];
        }
        $size = (int)($sizes[$i] ?? 0);
        if ($size <= 0) {
            return ['ok' => false, 'error' => '第 ' . ($idx + 1) . ' 张图片无效。'];
        }
        if ($size > $maxInputBytes) {
            $mb = (int)ceil($maxInputBytes / 1024 / 1024);
            return ['ok' => false, 'error' => '第 ' . ($idx + 1) . ' 张图片过大，请选择小于 ' . $mb . 'MB 的图片。'];
        }

        $tmp = (string)($tmpNames[$i] ?? '');
        if ($tmp === '' || !is_uploaded_file($tmp)) {
            return ['ok' => false, 'error' => '第 ' . ($idx + 1) . ' 张图片文件无效。'];
        }

        $mime = '';
        if (function_exists('finfo_open')) {
            $fi = finfo_open(FILEINFO_MIME_TYPE);
            if ($fi) {
                $mime = (string)finfo_file($fi, $tmp);
                finfo_close($fi);
            }
        }

        $allowed = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        if (!isset($allowed[$mime])) {
            return ['ok' => false, 'error' => '仅支持 jpg/png/gif/webp 图片。'];
        }
        $ext = $allowed[$mime];

        $name = date('YmdHis') . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        $dest = rtrim($dir, '/') . '/' . $name;

        $saved = false;
        if (in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true) && function_exists('getimagesize')) {
            $info = @getimagesize($tmp);
            $w = (int)($info[0] ?? 0);
            $h = (int)($info[1] ?? 0);
            if ($w > 0 && $h > 0 && function_exists('imagecreatetruecolor')) {
                $scale = 1.0;
                $maxSide = max($w, $h);
                if ($maxSide > $maxPixels) {
                    $scale = $maxPixels / $maxSide;
                }
                $nw = max(1, (int)floor($w * $scale));
                $nh = max(1, (int)floor($h * $scale));

                $src = null;
                if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) {
                    $src = @imagecreatefromjpeg($tmp);
                } elseif ($mime === 'image/png' && function_exists('imagecreatefrompng')) {
                    $src = @imagecreatefrompng($tmp);
                } elseif ($mime === 'image/webp' && function_exists('imagecreatefromwebp')) {
                    $src = @imagecreatefromwebp($tmp);
                }

                if ($src) {
                    $dst = imagecreatetruecolor($nw, $nh);
                    if ($mime === 'image/png') {
                        imagealphablending($dst, false);
                        imagesavealpha($dst, true);
                    }
                    @imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);

                    if ($mime === 'image/jpeg' && function_exists('imagejpeg')) {
                        $saved = @imagejpeg($dst, $dest, 82);
                    } elseif ($mime === 'image/png' && function_exists('imagepng')) {
                        $saved = @imagepng($dst, $dest, 6);
                    } elseif ($mime === 'image/webp' && function_exists('imagewebp')) {
                        $saved = @imagewebp($dst, $dest, 82);
                    }

                    imagedestroy($dst);
                    imagedestroy($src);
                }
            }
        }

        if (!$saved) {
            if (!move_uploaded_file($tmp, $dest)) {
                return ['ok' => false, 'error' => '保存图片失败，请检查 uploads 目录权限。'];
            }
        }

        $webPath = '/uploads/' . trim($subDir, '/') . '/' . $name;
        $paths[] = $webPath;
    }

    return ['ok' => true, 'paths' => $paths];
}

