<?php $attributes ??= new \Illuminate\View\ComponentAttributeBag;

$__newAttributes = [];
$__propNames = \Illuminate\View\ComponentAttributeBag::extractPropNames(([
'name' => null,
'type' => 'text',
'value' => null,
'invalid' => false,
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
'name' => null,
'type' => 'text',
'value' => null,
'invalid' => false,
]), 'is_string', ARRAY_FILTER_USE_KEY) as $__key => $__value) {
    $$__key = $$__key ?? $__value;
}

$__defined_vars = get_defined_vars();

foreach ($attributes->all() as $__key => $__value) {
    if (array_key_exists($__key, $__defined_vars)) unset($$__key);
}

unset($__defined_vars, $__key, $__value); ?>

<?php
$hasError = $invalid || ($name && $errors->has($name));
$base = 'w-full rounded-control border bg-surface px-3 py-2 text-sm text-text '
. 'placeholder:text-muted transition-colors disabled:bg-surface-muted disabled:text-muted '
. 'focus:outline-none focus:ring-2';
// An errored field keeps its danger colour even while focused, so the ring never
// turns green against a red border (the state must stay legible).
$state = $hasError
? 'border-danger focus:border-danger focus:ring-danger/30'
: 'border-border-strong focus:border-primary focus:ring-primary/40';
?>

<input
    <?php if($name): ?> name="<?php echo e($name); ?>" id="<?php echo e($name); ?>" <?php endif; ?>
    type="<?php echo e($type); ?>"
    <?php if($value !==null): ?> value="<?php echo e($value); ?>" <?php endif; ?>
    <?php echo e($attributes->merge(['class' => "$base $state"])); ?>

    <?php if($hasError): ?> aria-invalid="true" aria-describedby="<?php echo e($name); ?>-error" <?php endif; ?>><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/form/input.blade.php ENDPATH**/ ?>