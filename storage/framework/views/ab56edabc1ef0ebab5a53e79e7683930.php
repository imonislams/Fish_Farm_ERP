<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'variant' => 'primary', // primary | secondary | outline | ghost | danger | success
'size' => 'md', // sm | md
'type' => 'button',
'href' => null,
'icon' => null,
]));

foreach ($attributes->all() as $__key => $__value) {
    if (in_array($__key, $__propNames)) {
        $$__key = $$__key ?? $__value;
    } else {
        $__newAttributes[$__key] = $__value;
    }
}

$attributes = new \Illuminate\View\ComponentAttributeBag($__newAttributes);

unset($__propNames);
unset($__newAttributes);

foreach (array_filter(([
'variant' => 'primary', // primary | secondary | outline | ghost | danger | success
'size' => 'md', // sm | md
'type' => 'button',
'href' => null,
'icon' => null,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$variants = [
'primary' => 'gradient-primary text-white hover:opacity-95',
'secondary' => 'bg-secondary text-white hover:opacity-95',
'outline' => 'border border-border-strong bg-surface text-text-soft hover:bg-surface-muted',
'ghost' => 'text-text-soft hover:bg-surface-muted',
'danger' => 'bg-danger text-white hover:opacity-95',
'success' => 'bg-success text-white hover:opacity-95',
];
$sizes = [
'sm' => 'px-2.5 py-1.5 text-xs',
'md' => 'px-4 py-2 text-sm',
];
$classes = 'inline-flex items-center justify-center gap-2 rounded-control font-medium
transition-colors disabled:cursor-not-allowed disabled:opacity-50 whitespace-nowrap '
. ($variants[$variant] ?? $variants['primary']) . ' '
. ($sizes[$size] ?? $sizes['md']);
?>


<?php if($href): ?>
<a href="<?php echo e($href); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>>
    <?php if($icon): ?><?php if (isset($component)) { $__componentOriginalda1fff37ea857afed191643e958e2cb9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda1fff37ea857afed191643e958e2cb9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.icon','data' => ['name' => $icon,'class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $attributes = $__attributesOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__attributesOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $component = $__componentOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__componentOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?><?php endif; ?>
    <?php echo e($slot); ?>

</a>
<?php else: ?>
<button type="<?php echo e($type); ?>" <?php echo e($attributes->merge(['class' => $classes])); ?>>
    <?php if($icon): ?><?php if (isset($component)) { $__componentOriginalda1fff37ea857afed191643e958e2cb9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda1fff37ea857afed191643e958e2cb9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.icon','data' => ['name' => $icon,'class' => 'h-4 w-4']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($icon),'class' => 'h-4 w-4']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $attributes = $__attributesOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__attributesOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $component = $__componentOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__componentOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?><?php endif; ?>
    <?php echo e($slot); ?>

</button>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/button/button.blade.php ENDPATH**/ ?>