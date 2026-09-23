<!DOCTYPE html>
<html lang="<?php echo e(str_replace('_', '-', app()->getLocale())); ?>">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#14532d">

    <title><?php echo e($title ?? 'Sign in'); ?> &middot; <?php echo e(config('app.name')); ?></title>

    <link rel="manifest" href="<?php echo e(asset('manifest.webmanifest')); ?>">
    <link rel="icon" href="<?php echo e(asset('favicon.ico')); ?>" sizes="any">
    <link rel="apple-touch-icon" href="<?php echo e(asset('icons/apple-touch-icon.png')); ?>">

    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>

<?php
// Brand identity comes from the ONE company source — never hard-coded.
$brandName = \App\Support\CompanyContext::name();
$brandLogo = \App\Support\CompanyContext::logoUrl();
$brandInitials = \App\Support\CompanyContext::initials();
?>

<body class="min-h-screen bg-background text-text">

    <div class="grid min-h-screen lg:grid-cols-2">

        
        <aside class="gradient-hero relative hidden flex-col justify-between overflow-hidden p-10 text-white lg:flex xl:p-14">

            
            <div class="auth-brand-pattern" aria-hidden="true"></div>

            <div class="relative flex items-center gap-3">
                <?php if (isset($component)) { $__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth.brand-mark','data' => ['logo' => $brandLogo,'initials' => $brandInitials,'class' => 'h-11 w-11 text-lg']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth.brand-mark'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brandLogo),'initials' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brandInitials),'class' => 'h-11 w-11 text-lg']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70)): ?>
<?php $attributes = $__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70; ?>
<?php unset($__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70)): ?>
<?php $component = $__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70; ?>
<?php unset($__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70); ?>
<?php endif; ?>
                <span class="text-lg font-semibold"><?php echo e($brandName); ?></span>
            </div>

            <div class="relative max-w-md">
                <p class="text-xs font-semibold uppercase tracking-[0.18em] text-white/60">
                    Fish Farm ERP
                </p>

                <h1 class="mt-3 text-3xl font-bold leading-snug xl:text-4xl">
                    Manage your farm.
                    <br>Track your production.
                    <br>Grow your business.
                </h1>

                <p class="mt-4 text-sm text-white/75">
                    Ponds, fish stock, feed, FCR, sales and accounts &mdash; one system,
                    built on a real database instead of spreadsheets.
                </p>

                <ul class="mt-8 space-y-3 text-sm text-white/85">
                    <li class="flex items-center gap-3">
                        <?php if (isset($component)) { $__componentOriginalda1fff37ea857afed191643e958e2cb9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda1fff37ea857afed191643e958e2cb9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.icon','data' => ['name' => 'droplet','class' => 'h-4 w-4 shrink-0 text-white/70']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'droplet','class' => 'h-4 w-4 shrink-0 text-white/70']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $attributes = $__attributesOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__attributesOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $component = $__componentOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__componentOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
                        <span>Pond &amp; water management</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <?php if (isset($component)) { $__componentOriginalda1fff37ea857afed191643e958e2cb9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda1fff37ea857afed191643e958e2cb9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.icon','data' => ['name' => 'chart','class' => 'h-4 w-4 shrink-0 text-white/70']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'chart','class' => 'h-4 w-4 shrink-0 text-white/70']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $attributes = $__attributesOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__attributesOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $component = $__componentOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__componentOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
                        <span>Feed, FCR &amp; production analytics</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <?php if (isset($component)) { $__componentOriginalda1fff37ea857afed191643e958e2cb9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalda1fff37ea857afed191643e958e2cb9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.sidebar.icon','data' => ['name' => 'cart','class' => 'h-4 w-4 shrink-0 text-white/70']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('sidebar.icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'cart','class' => 'h-4 w-4 shrink-0 text-white/70']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $attributes = $__attributesOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__attributesOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalda1fff37ea857afed191643e958e2cb9)): ?>
<?php $component = $__componentOriginalda1fff37ea857afed191643e958e2cb9; ?>
<?php unset($__componentOriginalda1fff37ea857afed191643e958e2cb9); ?>
<?php endif; ?>
                        <span>Sales, customers &amp; accounts</span>
                    </li>
                </ul>
            </div>

            <p class="relative text-xs text-white/60">
                &copy; <?php echo e(date('Y')); ?> <?php echo e($brandName); ?>

            </p>
        </aside>

        
        <main class="flex items-center justify-center px-5 py-10 sm:py-12">
            <div class="w-full max-w-[26rem]">
                
                <div class="mb-7 flex items-center gap-3 lg:hidden">
                    <?php if (isset($component)) { $__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.auth.brand-mark','data' => ['logo' => $brandLogo,'initials' => $brandInitials,'class' => 'h-11 w-11 text-lg','gradient' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('auth.brand-mark'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['logo' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brandLogo),'initials' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute($brandInitials),'class' => 'h-11 w-11 text-lg','gradient' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70)): ?>
<?php $attributes = $__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70; ?>
<?php unset($__attributesOriginald791ce6b44c74e6a7bcdd435cd10bf70); ?>
<?php endif; ?>
<?php if (isset($__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70)): ?>
<?php $component = $__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70; ?>
<?php unset($__componentOriginald791ce6b44c74e6a7bcdd435cd10bf70); ?>
<?php endif; ?>
                    <div class="min-w-0">
                        <p class="truncate text-base font-semibold text-text"><?php echo e($brandName); ?></p>
                        <p class="text-xs text-muted">Fish Farm ERP</p>
                    </div>
                </div>

                <?php if (isset($component)) { $__componentOriginalfa2b54beb8b8457f1ab3fffa020f6016 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalfa2b54beb8b8457f1ab3fffa020f6016 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.toast.toast-stack','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('toast.toast-stack'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalfa2b54beb8b8457f1ab3fffa020f6016)): ?>
<?php $attributes = $__attributesOriginalfa2b54beb8b8457f1ab3fffa020f6016; ?>
<?php unset($__attributesOriginalfa2b54beb8b8457f1ab3fffa020f6016); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalfa2b54beb8b8457f1ab3fffa020f6016)): ?>
<?php $component = $__componentOriginalfa2b54beb8b8457f1ab3fffa020f6016; ?>
<?php unset($__componentOriginalfa2b54beb8b8457f1ab3fffa020f6016); ?>
<?php endif; ?>

                <div class="surface-card p-6 sm:p-7">
                    <?php echo e($slot); ?>

                </div>
            </div>
        </main>
    </div>

    <?php if (isset($component)) { $__componentOriginal3ad1f014f5cea3a81535e44d77ce3fec = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3ad1f014f5cea3a81535e44d77ce3fec = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout.network-status','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout.network-status'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3ad1f014f5cea3a81535e44d77ce3fec)): ?>
<?php $attributes = $__attributesOriginal3ad1f014f5cea3a81535e44d77ce3fec; ?>
<?php unset($__attributesOriginal3ad1f014f5cea3a81535e44d77ce3fec); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3ad1f014f5cea3a81535e44d77ce3fec)): ?>
<?php $component = $__componentOriginal3ad1f014f5cea3a81535e44d77ce3fec; ?>
<?php unset($__componentOriginal3ad1f014f5cea3a81535e44d77ce3fec); ?>
<?php endif; ?>
</body>

</html><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/components/layout/auth.blade.php ENDPATH**/ ?>