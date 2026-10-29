@extends('super-admin.layouts.app')

@section('title', 'Profile | Artflow')
@section('heading', 'Profile')

@section('content')
    <div class="mb-6 flex items-center justify-between">
        <div>
            <p class="text-sm text-slate-500">Manage your super admin account details and preferences.</p>
        </div>
        <a href="{{ route('super-admin.dashboard') }}" class="rounded-xl bg-slate-950 px-4 py-2 text-sm font-semibold text-white">Back to Dashboard</a>
    </div>

    @if(session('success'))
        <div class="mb-4 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="POST" action="{{ route('super-admin.profile.update') }}" enctype="multipart/form-data" class="grid gap-4 md:grid-cols-2">
            @csrf
            @method('PUT')

            <div class="md:col-span-2 flex items-center gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                <div class="relative h-20 w-20 shrink-0">
                    <img id="profile-preview" src="{{ $user->profile_picture ? asset('storage/' . $user->profile_picture) : '' }}" alt="Profile picture" class="{{ $user->profile_picture ? '' : 'hidden' }} h-20 w-20 rounded-full object-cover border border-slate-200">
                    <div id="profile-fallback" class="{{ $user->profile_picture ? 'hidden' : '' }} flex h-20 w-20 items-center justify-center rounded-full bg-slate-900 text-lg font-bold text-white">{{ strtoupper(substr($user->name ?? 'A', 0, 1)) }}</div>
                    <button type="button" id="profile-edit" class="absolute -bottom-1 -right-1 flex h-8 w-8 items-center justify-center rounded-full border border-slate-200 bg-white text-slate-700 shadow hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400" aria-label="Edit profile picture" title="Edit profile picture">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4" aria-hidden="true"><path d="M13.586 3.586a2 2 0 1 1 2.828 2.828l-.793.793-2.828-2.828.793-.793ZM11.379 5.793 3 14.172V17h2.828l8.38-8.379-2.83-2.828Z"/></svg>
                    </button>
                </div>

                <div class="flex-1">
                    <label for="profile-picture-input" class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Profile Picture</label>
                    <input id="profile-picture-input" type="file" name="profile_picture" accept="image/*" class="w-full rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                    <p id="profile-picture-hint" class="mt-1 text-xs text-slate-500">JPG, PNG or WebP. The preview updates as soon as you pick a file; click Save Profile to keep it.</p>
                    @error('profile_picture')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Full Name</label>
                <input type="text" name="name" value="{{ old('name', $user->name) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('name')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Father Name</label>
                <input type="text" name="fname" value="{{ old('fname', $user->fname) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('fname')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Email Address</label>
                <input type="email" name="email" value="{{ old('email', $user->email) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('email')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Phone Number</label>
                <input type="text" name="number" value="{{ old('number', $user->number) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('number')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div>
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">CNIC</label>
                <input type="text" name="cnic" value="{{ old('cnic', $user->cnic) }}" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">
                @error('cnic')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div class="md:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">Address</label>
                <textarea name="address" rows="4" class="w-full rounded-xl border border-slate-300 bg-slate-50 px-3 py-2 text-sm focus:border-slate-400 focus:outline-none">{{ old('address', $user->address) }}</textarea>
                @error('address')<div class="mt-1 text-xs text-rose-600">{{ $message }}</div>@enderror
            </div>

            <div class="md:col-span-2 flex justify-end">
                <button type="submit" class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-semibold text-white">Save Profile</button>
            </div>
        </form>
    </div>

    {{-- Picture editor: drag to reposition, zoom, rotate. Apply writes the crop back into the file input. --}}
    <div id="profile-cropper" role="dialog" aria-modal="true" aria-labelledby="profile-crop-title" style="display:none;position:fixed;inset:0;z-index:9999;align-items:center;justify-content:center;padding:16px;background:rgba(15,23,42,.6)">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h2 id="profile-crop-title" class="text-lg font-semibold text-slate-900">Edit profile picture</h2>
                    <p class="text-sm text-slate-500">Drag to position the picture. Use the slider to zoom.</p>
                </div>
                <button type="button" id="profile-crop-close" class="text-2xl leading-none text-slate-400 hover:text-slate-700" aria-label="Close editor">&times;</button>
            </div>

            <div class="flex justify-center rounded-2xl bg-slate-100 p-4">
                <canvas id="profile-crop-canvas" width="320" height="320" style="width:320px;max-width:100%;height:auto;aspect-ratio:1;border-radius:50%;cursor:move;touch-action:none;background:#fff;box-shadow:inset 0 0 0 1px #cbd5e1"></canvas>
            </div>

            <div class="mt-4 space-y-4">
                <label class="block text-xs font-semibold uppercase tracking-[0.22em] text-slate-500">
                    Zoom
                    <input id="profile-crop-zoom" type="range" min="1" max="3" step="0.01" value="1" class="mt-2 w-full">
                </label>
                <div class="flex gap-2">
                    <button type="button" id="profile-crop-rotate-left" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Rotate left</button>
                    <button type="button" id="profile-crop-rotate-right" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Rotate right</button>
                    <button type="button" id="profile-crop-reset" class="rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Reset</button>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" id="profile-crop-cancel" class="rounded-xl border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Cancel</button>
                    <button type="button" id="profile-crop-apply" class="rounded-xl bg-slate-950 px-5 py-2 text-sm font-semibold text-white">Apply</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const input = document.getElementById('profile-picture-input');
            const editButton = document.getElementById('profile-edit');
            const preview = document.getElementById('profile-preview');
            const fallback = document.getElementById('profile-fallback');
            const hint = document.getElementById('profile-picture-hint');

            const modal = document.getElementById('profile-cropper');
            const canvas = document.getElementById('profile-crop-canvas');
            const ctx = canvas.getContext('2d');
            const zoomInput = document.getElementById('profile-crop-zoom');

            const VIEW = 320;      // on-screen editor size (px)
            const OUTPUT = 512;    // saved picture size (px)

            let image = null;
            let sourceName = 'profile.jpg';
            let zoom = 1;
            let rotation = 0;
            let offsetX = 0;
            let offsetY = 0;
            let drag = null;

            const rotated = () => rotation % 180 !== 0;
            const imageW = () => (rotated() ? image.naturalHeight : image.naturalWidth);
            const imageH = () => (rotated() ? image.naturalWidth : image.naturalHeight);
            // Scale (in preview pixels per image pixel) that covers the circle at the current zoom.
            const baseScale = () => Math.max(VIEW / imageW(), VIEW / imageH()) * zoom;

            // Keep the circle inside the picture so no empty corners end up in the saved file.
            const clampOffsets = () => {
                const s = baseScale();
                const limitX = Math.max(0, (image.naturalWidth * s) / 2 - VIEW / 2);
                const limitY = Math.max(0, (image.naturalHeight * s) / 2 - VIEW / 2);
                offsetX = Math.min(limitX, Math.max(-limitX, offsetX));
                offsetY = Math.min(limitY, Math.max(-limitY, offsetY));
            };

            const drawTo = (context, size) => {
                const k = size / VIEW;
                context.save();
                context.fillStyle = '#ffffff';
                context.fillRect(0, 0, size, size);
                context.translate(size / 2 + offsetX * k, size / 2 + offsetY * k);
                context.rotate((rotation * Math.PI) / 180);
                const s = baseScale() * k;
                context.scale(s, s);
                context.drawImage(image, -image.naturalWidth / 2, -image.naturalHeight / 2);
                context.restore();
            };

            const draw = () => {
                if (image) {
                    clampOffsets();
                    drawTo(ctx, VIEW);
                }
            };

            const openEditor = (src, name) => new Promise((resolve, reject) => {
                const img = new Image();
                img.onload = () => {
                    image = img;
                    sourceName = name;
                    zoom = 1;
                    rotation = 0;
                    offsetX = 0;
                    offsetY = 0;
                    zoomInput.value = '1';
                    modal.style.display = 'flex';
                    draw();
                    resolve();
                };
                img.onerror = reject;
                img.src = src;
            });

            const closeEditor = () => {
                modal.style.display = 'none';
                drag = null;
            };

            const showPreview = (src, name) => {
                preview.src = src;
                preview.classList.remove('hidden');
                fallback.classList.add('hidden');
                hint.textContent = 'New picture ready: ' + name + '. Click Save Profile to keep it.';
            };

            editButton.addEventListener('click', () => {
                const file = input.files && input.files[0];
                if (file) {
                    openEditor(URL.createObjectURL(file), file.name);
                } else if (preview.getAttribute('src')) {
                    openEditor(preview.src, 'profile.jpg');
                } else {
                    input.click();
                }
            });

            input.addEventListener('change', () => {
                const file = input.files && input.files[0];
                if (!file || !file.type.startsWith('image/')) {
                    return;
                }
                showPreview(URL.createObjectURL(file), file.name);
                openEditor(URL.createObjectURL(file), file.name);
            });

            canvas.addEventListener('pointerdown', (event) => {
                drag = { x: event.clientX, y: event.clientY, ox: offsetX, oy: offsetY };
                canvas.setPointerCapture(event.pointerId);
            });
            canvas.addEventListener('pointermove', (event) => {
                if (!drag) return;
                offsetX = drag.ox + (event.clientX - drag.x);
                offsetY = drag.oy + (event.clientY - drag.y);
                draw();
            });
            const endDrag = () => { drag = null; };
            canvas.addEventListener('pointerup', endDrag);
            canvas.addEventListener('pointercancel', endDrag);

            zoomInput.addEventListener('input', () => {
                zoom = parseFloat(zoomInput.value);
                draw();
            });

            document.getElementById('profile-crop-rotate-left').addEventListener('click', () => {
                rotation = (rotation + 270) % 360;
                offsetX = 0;
                offsetY = 0;
                draw();
            });
            document.getElementById('profile-crop-rotate-right').addEventListener('click', () => {
                rotation = (rotation + 90) % 360;
                offsetX = 0;
                offsetY = 0;
                draw();
            });
            document.getElementById('profile-crop-reset').addEventListener('click', () => {
                zoom = 1;
                rotation = 0;
                offsetX = 0;
                offsetY = 0;
                zoomInput.value = '1';
                draw();
            });

            document.getElementById('profile-crop-cancel').addEventListener('click', closeEditor);
            document.getElementById('profile-crop-close').addEventListener('click', closeEditor);
            document.addEventListener('keydown', (event) => {
                if (event.key === 'Escape' && modal.style.display === 'flex') closeEditor();
            });

            document.getElementById('profile-crop-apply').addEventListener('click', () => {
                const output = document.createElement('canvas');
                output.width = OUTPUT;
                output.height = OUTPUT;
                drawTo(output.getContext('2d'), OUTPUT);

                output.toBlob((blob) => {
                    if (!blob) return;
                    const name = sourceName.replace(/\.[^.]+$/, '') + '.jpg';
                    const file = new File([blob], name, { type: 'image/jpeg' });
                    const transfer = new DataTransfer();
                    transfer.items.add(file);
                    input.files = transfer.files;
                    showPreview(URL.createObjectURL(blob), name);
                    closeEditor();
                }, 'image/jpeg', 0.92);
            });
        })();
    </script>
@endsection
