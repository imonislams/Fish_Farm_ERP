

<?php
$flash = array_filter([
'success' => session('success') ?? session('status'),
'error' => session('error'),
'warning' => session('warning'),
'info' => session('info'),
]);
?>

<div
    id="toast-stack"
    class="pointer-events-none fixed right-4 top-4 z-[60] flex w-[calc(100%-2rem)] max-w-sm flex-col gap-2"
    aria-live="polite"
    aria-atomic="true">
    <?php $__currentLoopData = $flash; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $tone => $message): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
    <div
        class="toast pointer-events-auto surface-card flex items-start gap-3 border-l-4 p-3"
        data-tone="<?php echo e($tone); ?>"
        role="status"
        style="border-left-color: var(--color-<?php echo e($tone === 'error' ? 'danger' : $tone); ?>)">
        <span class="text-sm text-text-soft"><?php echo e($message); ?></span>

        <button
            type="button"
            class="ml-auto shrink-0 rounded p-0.5 text-muted hover:text-text"
            data-toast-close
            aria-label="Dismiss notification">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>
    </div>
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
</div><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/toast/toast-stack.blade.php ENDPATH**/ ?>