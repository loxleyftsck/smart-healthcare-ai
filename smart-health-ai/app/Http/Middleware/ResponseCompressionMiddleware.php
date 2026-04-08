<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * ResponseCompressionMiddleware - GZIP compress large responses
 * PATH B Day 3: Reduce response payload size (up to 70% compression)
 * 
 * Automatically compresses responses > 1KB to gzip
 * Respects client Accept-Encoding header
 */
class ResponseCompressionMiddleware
{
    /**
     * Minimum response size to compress (bytes)
     */
    public const MIN_COMPRESSION_SIZE = 1024; // 1KB
    
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        
        // Only compress if client accepts gzip
        if (!$this->shouldCompress($request, $response)) {
            return $response;
        }
        
        // Compress response content
        $content = $response->getContent();
        
        if (strlen($content) > self::MIN_COMPRESSION_SIZE) {
            $compressed = gzencode($content, 6); // 6 = balanced compression
            
            if ($compressed !== false) {
                $response->setContent($compressed);
                $response->headers->set('Content-Encoding', 'gzip');
                $response->headers->set('Content-Length', strlen($compressed));
                
                // Remove Content-Length if it was set to original size
                if ($response->headers->has('Content-Length')) {
                    $response->headers->set('Content-Length', strlen($compressed));
                }
            }
        }
        
        return $response;
    }
    
    /**
     * Determine if response should be compressed
     *
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    private function shouldCompress(Request $request, Response $response): bool
    {
        // Check if client accepts gzip
        $acceptEncoding = $request->header('Accept-Encoding', '');
        if (stripos($acceptEncoding, 'gzip') === false) {
            return false;
        }
        
        // Don't compress if already compressed
        if ($response->headers->has('Content-Encoding')) {
            return false;
        }
        
        // Only compress text-based responses
        $contentType = $response->headers->get('Content-Type', '');
        $compressibleTypes = [
            'application/json',
            'text/html',
            'text/plain',
            'text/xml',
            'application/xml',
            'application/x-www-form-urlencoded',
        ];
        
        if (empty($contentType)) {
            return true; // Default to compress
        }
        
        foreach ($compressibleTypes as $type) {
            if (stripos($contentType, $type) !== false) {
                return true;
            }
        }
        
        return false;
    }
}
