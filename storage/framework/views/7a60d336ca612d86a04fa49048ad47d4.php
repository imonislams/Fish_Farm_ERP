<?php if (isset($component)) { $__componentOriginal1d4e40438baebe03e79704896ba2f23a = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1d4e40438baebe03e79704896ba2f23a = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.layout.auth','data' => ['title' => 'Sign in']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('layout.auth'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Sign in']); ?>

    <div class="auth-enter">
        <h1 class="text-xl font-bold text-text sm:text-2xl">Welcome back</h1>
        <p class="mt-1.5 text-sm text-muted">
            Sign in to continue to your Fish Farm ERP account.
        </p>

        <?php
        // A normal credential error is shown once, under the email field, by
        // x-form.field. The banner is reserved for the two account-level messages
        // that have no field of their own: throttling and a deactivated account.
        $emailError = $errors->first('email');
        $accountError = ($emailError && (str_contains($emailError, 'Too many') || str_contains($emailError, 'deactivated')))
            ? $emailError
            : null;
        ?>

        <?php if($accountError): ?>
        <div class="mt-5">
            <?php if (isset($component)) { $__componentOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.alert.alert','data' => ['tone' => 'error']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('alert.alert'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['tone' => 'error']); ?><?php echo e($accountError); ?> <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b)): ?>
<?php $attributes = $__attributesOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b; ?>
<?php unset($__attributesOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b)): ?>
<?php $component = $__componentOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b; ?>
<?php unset($__componentOriginal6ff20a3d3ab12f313ed285f7a8ce0b1b); ?>
<?php endif; ?>
        </div>
        <?php endif; ?>

        <form
            method="POST"
            action="<?php echo e(route('login.store')); ?>"
            class="mt-6 space-y-4"
            data-login-form
            novalidate>
            <?php echo csrf_field(); ?>

            
            <?php if (isset($component)) { $__componentOriginal45920e144996b26f3340500ed9e02bd3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal45920e144996b26f3340500ed9e02bd3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.field','data' => ['label' => 'Email address','name' => 'email','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Email address','name' => 'email','required' => true]); ?>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 7l9 6 9-6M5 5h14a2 2 0 012 2v10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2z" />
                        </svg>
                    </span>
                    <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'email','type' => 'email','value' => old('email'),'autocomplete' => 'username','autofocus' => true,'required' => true,'inputmode' => 'email','placeholder' => 'Enter your email address','class' => 'pl-9']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'email','type' => 'email','value' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(old('email')),'autocomplete' => 'username','autofocus' => true,'required' => true,'inputmode' => 'email','placeholder' => 'Enter your email address','class' => 'pl-9']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $attributes = $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $component = $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal45920e144996b26f3340500ed9e02bd3)): ?>
<?php $attributes = $__attributesOriginal45920e144996b26f3340500ed9e02bd3; ?>
<?php unset($__attributesOriginal45920e144996b26f3340500ed9e02bd3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal45920e144996b26f3340500ed9e02bd3)): ?>
<?php $component = $__componentOriginal45920e144996b26f3340500ed9e02bd3; ?>
<?php unset($__componentOriginal45920e144996b26f3340500ed9e02bd3); ?>
<?php endif; ?>

            
            <?php if (isset($component)) { $__componentOriginal45920e144996b26f3340500ed9e02bd3 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal45920e144996b26f3340500ed9e02bd3 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.field','data' => ['label' => 'Password','name' => 'password','required' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.field'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['label' => 'Password','name' => 'password','required' => true]); ?>
                <div class="relative">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-muted"
                        aria-hidden="true">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M7 11V8a5 5 0 0110 0v3M6 11h12a1 1 0 011 1v7a1 1 0 01-1 1H6a1 1 0 01-1-1v-7a1 1 0 011-1z" />
                        </svg>
                    </span>
                    <?php if (isset($component)) { $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.form.input','data' => ['name' => 'password','type' => 'password','autocomplete' => 'current-password','required' => true,'placeholder' => 'Enter your password','class' => 'pl-9 pr-11','dataPasswordInput' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('form.input'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['name' => 'password','type' => 'password','autocomplete' => 'current-password','required' => true,'placeholder' => 'Enter your password','class' => 'pl-9 pr-11','data-password-input' => true]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $attributes = $__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__attributesOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b)): ?>
<?php $component = $__componentOriginal5c2a97ab476b69c1189ee85d1a95204b; ?>
<?php unset($__componentOriginal5c2a97ab476b69c1189ee85d1a95204b); ?>
<?php endif; ?>

                    
                    <button
                        type="button"
                        class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-control text-muted transition-colors hover:text-text-soft focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                        data-password-toggle
                        aria-label="Show password"
                        aria-pressed="false">
                        <svg data-eye-show class="h-4 w-4" fill="none" stroke="currentColor"
                            stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg data-eye-hide class="hidden h-4 w-4" fill="none" stroke="currentColor"
                            stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M3 3l18 18M10.6 6.1A9.7 9.7 0 0112 6c6 0 9.5 6 9.5 6a17 17 0 01-3.2 3.8M6.4 7.4A17 17 0 002.5 13s3.5 6 9.5 6a9.6 9.6 0 003.5-.65" />
                        </svg>
                    </button>
                </div>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal45920e144996b26f3340500ed9e02bd3)): ?>
<?php $attributes = $__attributesOriginal45920e144996b26f3340500ed9e02bd3; ?>
<?php unset($__attributesOriginal45920e144996b26f3340500ed9e02bd3); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal45920e144996b26f3340500ed9e02bd3)): ?>
<?php $component = $__componentOriginal45920e144996b26f3340500ed9e02bd3; ?>
<?php unset($__componentOriginal45920e144996b26f3340500ed9e02bd3); ?>
<?php endif; ?>

            <div class="flex items-center justify-between gap-3">
                <label class="group flex cursor-pointer items-center gap-2 text-sm text-text-soft">
                    <input
                        type="checkbox"
                        name="remember"
                        value="1"
                        <?php if(old('remember')): echo 'checked'; endif; ?>
                        class="h-4 w-4 shrink-0 cursor-pointer rounded border-border-strong text-primary accent-primary focus:ring-2 focus:ring-primary/40 focus:ring-offset-0">
                    <span class="select-none group-hover:text-text">Remember me</span>
                </label>
            </div>

            <?php if (isset($component)) { $__componentOriginal3eeffba379863b544c306a87e2e0254d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal3eeffba379863b544c306a87e2e0254d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.button.button','data' => ['type' => 'submit','variant' => 'primary','class' => 'w-full !py-2.5','dataLoginSubmit' => true]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('button.button'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['type' => 'submit','variant' => 'primary','class' => 'w-full !py-2.5','data-login-submit' => true]); ?>
                <span data-login-label>Sign in</span>
             <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal3eeffba379863b544c306a87e2e0254d)): ?>
<?php $attributes = $__attributesOriginal3eeffba379863b544c306a87e2e0254d; ?>
<?php unset($__attributesOriginal3eeffba379863b544c306a87e2e0254d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal3eeffba379863b544c306a87e2e0254d)): ?>
<?php $component = $__componentOriginal3eeffba379863b544c306a87e2e0254d; ?>
<?php unset($__componentOriginal3eeffba379863b544c306a87e2e0254d); ?>
<?php endif; ?>
        </form>

        <p class="mt-6 flex items-start gap-2 text-xs text-muted">
            <svg class="mt-0.5 h-3.5 w-3.5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8"
                viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round"
                    d="M12 3l7 3v5c0 4.5-3 8.3-7 9.5C8 19.3 5 15.5 5 11V6l7-3z" />
            </svg>
            <span>Access is restricted to authorised staff. Contact your administrator
                if you cannot sign in.</span>
        </p>
    </div>

 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1d4e40438baebe03e79704896ba2f23a)): ?>
<?php $attributes = $__attributesOriginal1d4e40438baebe03e79704896ba2f23a; ?>
<?php unset($__attributesOriginal1d4e40438baebe03e79704896ba2f23a); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1d4e40438baebe03e79704896ba2f23a)): ?>
<?php $component = $__componentOriginal1d4e40438baebe03e79704896ba2f23a; ?>
<?php unset($__componentOriginal1d4e40438baebe03e79704896ba2f23a); ?>
<?php endif; ?><?php /**PATH C:\xampp\htdocs\Fish-Farm_ERP\resources\views/auth/login.blade.php ENDPATH**/ ?>