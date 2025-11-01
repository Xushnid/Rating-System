<?php

namespace App;

/**
 * Centralized Error Handler
 * Handles all application errors securely
 */
class ErrorHandler
{
    /**
     * Handle application errors securely
     */
    public static function handleError($errno, $errstr, $errfile, $errline)
    {
        // Log all errors
        $message = "Error [$errno]: $errstr in $errfile on line $errline";
        log_error($message);

        // Don't display errors in production
        if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
            return true; // Don't show error to user
        }

        return false; // Show error in development
    }

    /**
     * Handle uncaught exceptions
     */
    public static function handleException($exception)
    {
        // Log the exception
        $message = "Uncaught Exception: " . $exception->getMessage() . 
                  " in " . $exception->getFile() . 
                  " on line " . $exception->getLine();
        log_error($message);

        // Set appropriate HTTP status code
        http_response_code(500);

        // Check if it's an AJAX request
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                echo json_encode([
                    'success' => false,
                    'message' => 'Tizimda xatolik yuz berdi. Iltimos, keyinroq urinib ko\'ring.'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Development Error: ' . $exception->getMessage(),
                    'file' => $exception->getFile(),
                    'line' => $exception->getLine()
                ]);
            }
        } else {
            // Regular page request
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                self::showErrorPage();
            } else {
                echo "<h1>Development Error</h1>";
                echo "<p><strong>Message:</strong> " . htmlspecialchars($exception->getMessage()) . "</p>";
                echo "<p><strong>File:</strong> " . htmlspecialchars($exception->getFile()) . "</p>";
                echo "<p><strong>Line:</strong> " . $exception->getLine() . "</p>";
                echo "<pre>" . htmlspecialchars($exception->getTraceAsString()) . "</pre>";
            }
        }

        exit;
    }

    /**
     * Handle database errors specifically
     */
    public static function handleDatabaseError($exception, $sql = null, $params = [])
    {
        // Log detailed database error
        $message = "Database Error: " . $exception->getMessage();
        if ($sql) {
            $message .= " | SQL: " . $sql;
        }
        if (!empty($params)) {
            $message .= " | Params: " . json_encode($params);
        }
        log_error($message);

        // Return user-friendly error
        if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
            throw new \Exception('Ma\'lumotlar bazasida xatolik yuz berdi. Iltimos, administrator bilan bog\'laning.');
        } else {
            throw new \Exception('Database Error: ' . $exception->getMessage());
        }
    }

    /**
     * Show generic error page for production
     */
    private static function showErrorPage()
    {
        ?>
        <!DOCTYPE html>
        <html lang="uz">
        <head>
            <meta charset="UTF-8">
            <title>Xatolik - Rating System</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        </head>
        <body class="d-flex align-items-center justify-content-center min-vh-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <div class="card">
                            <div class="card-body p-5">
                                <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                                <h1 class="h3 mt-3 mb-3">Tizimda Xatolik</h1>
                                <p class="text-muted mb-4">
                                    Kechirasiz, tizimda vaqtincha xatolik yuz berdi. 
                                    Iltimos, keyinroq qayta urinib ko'ring yoki administrator bilan bog'laning.
                                </p>
                                <a href="/" class="btn btn-primary">
                                    <i class="bi bi-house me-2"></i>Bosh sahifaga qaytish
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Validate environment and setup error handling
     */
    public static function setup()
    {
        // Set error handler
        set_error_handler([self::class, 'handleError']);
        
        // Set exception handler
        set_exception_handler([self::class, 'handleException']);

        // Configure error reporting based on environment
        if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
            // Production: Log errors but don't display them
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
            ini_set('log_errors', 1);
            error_reporting(E_ALL);
        } else {
            // Development: Show all errors
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);
        }

        // Ensure log directory exists
        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
    }

    /**
     * Handle validation errors
     */
    public static function handleValidationError($message, $field = null)
    {
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => $message,
                'field' => $field
            ]);
            exit;
        } else {
            throw new \InvalidArgumentException($message);
        }
    }

    /**
     * Handle authentication errors
     */
    public static function handleAuthError($message = null)
    {
        $defaultMessage = 'Tizimga kirish talab qilinadi.';
        $message = $message ?: $defaultMessage;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode([
                'success' => false,
                'message' => $message,
                'redirect' => '/login'
            ]);
            exit;
        } else {
            header('Location: /login');
            exit;
        }
    }

    /**
     * Handle authorization errors
     */
    public static function handleAuthorizationError($message = null)
    {
        $defaultMessage = 'Bu amalni bajarish uchun ruxsat yo\'q.';
        $message = $message ?: $defaultMessage;

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            
            header('Content-Type: application/json');
            http_response_code(403);
            echo json_encode([
                'success' => false,
                'message' => $message
            ]);
            exit;
        } else {
            http_response_code(403);
            self::showAuthorizationErrorPage($message);
            exit;
        }
    }

    /**
     * Show authorization error page
     */
    private static function showAuthorizationErrorPage($message)
    {
        ?>
        <!DOCTYPE html>
        <html lang="uz">
        <head>
            <meta charset="UTF-8">
            <title>Ruxsat yo'q - Rating System</title>
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
            <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
        </head>
        <body class="d-flex align-items-center justify-content-center min-vh-100">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-md-6 text-center">
                        <div class="card">
                            <div class="card-body p-5">
                                <i class="bi bi-shield-exclamation text-danger" style="font-size: 4rem;"></i>
                                <h1 class="h3 mt-3 mb-3">Ruxsat Yo'q</h1>
                                <p class="text-muted mb-4">
                                    <?= htmlspecialchars($message) ?>
                                </p>
                                <a href="javascript:history.back()" class="btn btn-secondary me-2">
                                    <i class="bi bi-arrow-left me-2"></i>Orqaga
                                </a>
                                <a href="/" class="btn btn-primary">
                                    <i class="bi bi-house me-2"></i>Bosh sahifa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }
}