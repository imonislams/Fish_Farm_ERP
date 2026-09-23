<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames((['label' => null, 'name' => null, 'required' => false, 'hint' => null, 'for' => null]));

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

foreach (array_filter((['label' => null, 'name' => null, 'required' => false, 'hint' => null, 'for' => null]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>


<div <?php echo e($attributes->merge(['class' => 'space-y-1.5'])); ?>>
    <?php if($label): ?>
    <label for="<?php echo e($for ?? $name); ?>" class="block text-sm font-medium text-text-soft">
        <?php echo e($label); ?>

        <?php if($required): ?><span class="text-danger" aria-hidden="true">*</span><?php endif; ?>
    </label>
    <?php endif; ?>

    <?php echo e($slot); ?>


    <?php if($hint): ?>
    <p class="text-xs text-muted"><?php echo e($hint); ?></p>
    <?php endif; ?>

    <?php if($name && $errors->has($name)): ?>
    <p class="flex items-start gap-1.5 text-xs font-medium text-danger"
        id="<?php echo e($name); ?>-error" role="alert">
        <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="2"
            viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M12 9v4m0 4h.01M12 3a9 9 0 100 18 9 9 0 000-18z" />
        </svg>
        <span><?php echo e($errors->first($name)); ?></span>
    </p>
    <?php endif; ?>
</div><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/form/field.blade.php ENDPATH**/ ?>