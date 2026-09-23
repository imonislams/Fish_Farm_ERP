<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'logo' => null,
'initials' => 'FF',
'gradient' => false,
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
'logo' => null,
'initials' => 'FF',
'gradient' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>



<?php
$base = 'grid shrink-0 place-items-center overflow-hidden rounded-control font-bold ';
$tone = $gradient
? 'gradient-primary text-white'
: 'bg-white/15 text-white';
?>

<span <?php echo e($attributes->merge(['class' => $base . $tone])); ?>>
    <?php if($logo): ?>
    <img src="<?php echo e($logo); ?>" alt="" class="h-full w-full object-cover">
    <?php else: ?>
    <span aria-hidden="true"><?php echo e($initials); ?></span>
    <?php endif; ?>
</span><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/auth/brand-mark.blade.php ENDPATH**/ ?>