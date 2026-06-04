@extends('layouts.public')

@section('title', 'Book appointment — '.config('app.name'))

@section('header')
    @include('partials.booking-header')
@endsection

@section('content')
    @if (session('success'))
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="ui-alert-success">{{ session('success') }}</div>
        </div>
    @endif
    @if ($errors->any())
        <div class="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8">
            <div class="ui-alert-error" role="alert">{{ $errors->first() }}</div>
        </div>
    @endif

    <div class="ui-page max-w-7xl pb-16">
        <div class="mb-8 max-w-2xl">
            <span class="text-xs font-semibold uppercase tracking-widest text-cyan-700/90">Scheduling</span>
            <h2 class="ui-marketing-heading mt-2 text-3xl text-slate-900 sm:text-4xl">Book appointment</h2>
            <p class="mt-2 text-slate-600">Use <strong class="font-medium text-slate-800">Date</strong> to list doctors who work that day and to open slots from that day onward (then pick a day on the strip and your hours).</p>
        </div>

        <div class="ui-card p-5 sm:p-6">
            <form id="booking-filter-form" method="GET" action="{{ route('bookings.create') }}" class="flex flex-wrap items-end gap-4">
                <div class="w-full">
                    <label class="mb-2 block text-sm font-medium text-slate-700">Specialty</label>
                    <div class="mb-2 grid grid-cols-2 gap-2 md:grid-cols-4 lg:grid-cols-6">
                        <button
                            type="button"
                            data-specialty-id=""
                            class="specialty-chip rounded-xl border px-3 py-2 text-sm transition {{ $selectedSpecialtyId === '' ? 'border-cyan-600 bg-gradient-to-r from-cyan-600 to-teal-600 text-white shadow-md' : 'border-cyan-200 text-cyan-900 hover:bg-cyan-50' }}"
                        >
                            All specialties
                        </button>
                        @foreach($specialties as $specialty)
                            <button
                                type="button"
                                data-specialty-id="{{ $specialty->id }}"
                                class="specialty-chip rounded-xl border px-3 py-2 text-sm transition {{ (string)$selectedSpecialtyId === (string)$specialty->id ? 'border-cyan-600 bg-gradient-to-r from-cyan-600 to-teal-600 text-white shadow-md' : 'border-cyan-200 text-cyan-900 hover:bg-cyan-50' }}"
                            >
                                {{ $specialty->name }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="specialty_id" id="filter_specialty_id" value="{{ $selectedSpecialtyId }}">
                </div>

                <div class="min-w-[240px] flex-1">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Search doctor</label>
                    <input type="text" name="doctor_search" value="{{ $doctorSearch }}" placeholder="Doctor name or email" class="ui-input">
                </div>

                <div class="min-w-[200px]">
                    <label class="mb-1 block text-sm font-medium text-slate-700">Date</label>
                    <input type="date" name="appointment_date" value="{{ $selectedDate }}" min="{{ \Illuminate\Support\Carbon::today()->format('Y-m-d') }}" class="ui-input" title="Filter doctors who work this day; show available slots from this day for up to 60 days.">
                </div>

                <input type="hidden" name="doctor_id" value="{{ $selectedDoctorId }}">

                <button class="ui-btn-primary" type="submit" name="apply_filters" value="1">Apply filters</button>
            </form>
        </div>

        <div class="ui-card p-5 sm:p-6">
            <h3 class="ui-card-title mb-4">
                @if($selectedSpecialtyId === '')
                    All approved doctors
                @else
                    Doctors in selected specialty
                @endif
            </h3>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                @forelse($doctors as $doctor)
                    @php
                        $image = $doctor->doctorProfile?->profile_image;
                        $imageUrl = $image
                            ? (str_starts_with($image, 'http') ? $image : asset('storage/'.$image))
                            : null;
                    @endphp
                    <a
                        href="{{ route('bookings.create', array_merge(request()->query(), ['doctor_id' => $doctor->id])) }}"
                        class="doctor-pick-link {{ (string)$selectedDoctorId === (string)$doctor->id ? 'ring-2 ring-cyan-500 ring-offset-2 ' : '' }} ui-card ui-card-hover block p-4"
                    >
                        <div class="flex items-center gap-3">
                            @if($imageUrl)
                                <img src="{{ $imageUrl }}" alt="{{ $doctor->name }}" class="h-14 w-14 rounded-full border border-cyan-100 object-cover">
                            @else
                                <div class="flex h-14 w-14 items-center justify-center rounded-full bg-gradient-to-br from-cyan-100 to-teal-100 font-bold text-cyan-800">
                                    {{ strtoupper(substr($doctor->name, 0, 1)) }}
                                </div>
                            @endif
                            <div>
                                <h4 class="font-display font-semibold text-slate-900">{{ $doctor->name }}</h4>
                                <p class="text-xs text-slate-500">{{ $doctor->doctorSpecialties->pluck('name')->join(', ') }}</p>
                            </div>
                        </div>
                        <div class="mt-3 text-sm">
                            <span class="text-slate-500">Hourly rate (€):</span>
                            <span class="font-semibold text-emerald-700">@eur($doctor->doctorProfile?->hourly_rate ?? 0)</span>
                        </div>
                    </a>
                @empty
                    <div class="col-span-full text-sm text-slate-500">
                        @if($selectedSpecialtyId === '')
                            No approved doctors at the moment.
                        @else
                            No approved doctors for this specialty.
                        @endif
                    </div>
                @endforelse
            </div>
        </div>

        @php
            $slotsByDate = $availableSlotsByDate ?? [];
            $hasAnySlot = collect($slotsByDate)->flatten()->isNotEmpty();
            $slotDateKeys = array_keys($slotsByDate);
            $firstSlotDate = $slotDateKeys[0] ?? null;
            $activeSlotDate = ($firstSlotDate && isset($slotsByDate[$selectedDate])) ? $selectedDate : $firstSlotDate;
        @endphp
        @if($selectedDoctorId !== '' && ($effectiveSpecialtyId ?? '') !== '' && $hasAnySlot)
            <div class="ui-card p-6 sm:p-8">
                <div class="mb-6">
                    <h3 class="ui-card-title">Available times</h3>
                    <p class="mt-1 text-sm text-slate-600">Swipe the days horizontally, then choose one hour — tapping a different hour replaces the previous selection.</p>
                </div>

                <form method="POST" action="{{ route('bookings.store') }}" class="space-y-4" id="booking-form">
                    @csrf
                    <input type="hidden" name="specialty_id" value="{{ $effectiveSpecialtyId }}">
                    <input type="hidden" name="doctor_id" value="{{ $selectedDoctorId }}">
                    <input type="hidden" name="appointment_date" value="{{ $selectedDate }}" id="appointment_date_booking">

                    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2 lg:gap-8">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Day</label>
                            <div
                                id="booking-date-strip"
                                class="-mx-1 mb-4 flex snap-x snap-mandatory gap-2 overflow-x-auto px-1 pb-2 pt-0.5 [scrollbar-width:thin]"
                                role="tablist"
                                aria-label="Available days"
                            >
                                @foreach($slotsByDate as $workDate => $times)
                                    @php
                                        $d = \Illuminate\Support\Carbon::parse($workDate);
                                        $isActive = (string) $workDate === (string) $activeSlotDate;
                                    @endphp
                                    <button
                                        type="button"
                                        role="tab"
                                        aria-selected="{{ $isActive ? 'true' : 'false' }}"
                                        data-work-date="{{ $workDate }}"
                                        class="date-pill shrink-0 snap-start rounded-2xl border px-3 py-2.5 text-left text-sm transition sm:min-w-[5.5rem] {{ $isActive ? 'border-cyan-600 bg-gradient-to-br from-cyan-600 to-teal-600 text-white shadow-md ring-2 ring-cyan-500/40' : 'border-slate-200 bg-white text-slate-800 shadow-sm hover:border-cyan-300 hover:bg-cyan-50/90' }}"
                                    >
                                        <span class="block text-[10px] font-semibold uppercase tracking-wider opacity-70">{{ $d->translatedFormat('D') }}</span>
                                        <span class="block text-lg font-bold leading-tight">{{ $d->format('j') }}</span>
                                        <span class="block text-[11px] opacity-75">{{ $d->translatedFormat('M') }}</span>
                                        <span class="pill-count mt-1 inline-block rounded-full px-1.5 py-0.5 text-[10px] font-medium {{ $isActive ? 'bg-white/20 text-white' : 'bg-slate-100 text-slate-600' }}">{{ count($times) }} {{ count($times) === 1 ? 'slot' : 'slots' }}</span>
                                    </button>
                                @endforeach
                            </div>

                            <div id="active-day-heading" class="mb-3 rounded-xl border border-cyan-100 bg-cyan-50/50 px-4 py-3 text-sm text-slate-700">
                                <span class="font-semibold text-slate-900" data-heading-text>{{ \Illuminate\Support\Carbon::parse($activeSlotDate)->translatedFormat('l, j F Y') }}</span>
                            </div>

                            <label class="mb-2 block text-sm font-medium text-slate-700">Hours this day</label>
                            <div id="day-panels" class="rounded-xl border border-slate-100 bg-slate-50/40 p-4">
                                @foreach($slotsByDate as $workDate => $times)
                                    <div
                                        class="day-slot-panel {{ (string) $workDate === (string) $activeSlotDate ? '' : 'hidden' }}"
                                        data-work-date="{{ $workDate }}"
                                        data-heading="{{ \Illuminate\Support\Carbon::parse($workDate)->translatedFormat('l, j F Y') }}"
                                        role="tabpanel"
                                    >
                                        <div class="grid max-h-[min(40vh,22rem)] grid-cols-4 gap-2 overflow-y-auto pr-1 sm:grid-cols-5 md:grid-cols-6">
                                            @foreach($times as $time)
                                                <button
                                                    type="button"
                                                    data-slot="{{ $time }}"
                                                    data-work-date="{{ $workDate }}"
                                                    class="hour-slot rounded-lg border border-cyan-200 px-2 py-2 text-center text-sm font-medium text-cyan-900 transition hover:border-cyan-400 hover:bg-cyan-50"
                                                >
                                                    {{ $time }}
                                                </button>
                                            @endforeach
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <p class="mt-2 text-xs text-slate-500">One hour per booking — tap another slot to replace your choice. Scroll inside the grid if there are many hours in one day.</p>
                            <div id="selected-slots-inputs"></div>
                        </div>

                        <div class="lg:sticky lg:top-24 lg:self-start">
                            <label class="mb-1 block text-sm font-medium text-slate-700">Payment method</label>
                            <select name="payment_method" id="payment_method" class="ui-input" required>
                                <option value="cash" @selected(old('payment_method', 'cash') === 'cash')>Cash</option>
                                <option value="card" @selected(old('payment_method') === 'card')>Card</option>
                            </select>
                        </div>
                    </div>

                    <div id="card-fields-wrapper" class="hidden space-y-3">
                        <div class="flex items-center gap-2 text-xs">
                            <span class="rounded bg-cyan-100 px-2 py-1 font-semibold text-cyan-800">VISA</span>
                            <span class="rounded bg-amber-100 px-2 py-1 font-semibold text-amber-800">MasterCard</span>
                            <span class="text-slate-500">Demo payment gateway.</span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-2">
                            <select name="card_type" class="ui-input card-field">
                                <option value="visa" @selected(old('card_type', 'visa') === 'visa')>Visa</option>
                                <option value="mastercard" @selected(old('card_type') === 'mastercard')>MasterCard</option>
                            </select>
                            <input type="text" name="cardholder_name" value="{{ old('cardholder_name') }}" placeholder="Cardholder name" class="ui-input card-field">
                            <input type="text" name="card_number" value="{{ old('card_number') }}" placeholder="Card number" class="ui-input card-field">
                            <div class="grid grid-cols-2 gap-2">
                                <input type="text" name="expiry_month" value="{{ old('expiry_month') }}" placeholder="MM" class="ui-input card-field">
                                <input type="text" name="expiry_year" value="{{ old('expiry_year') }}" placeholder="YY" class="ui-input card-field">
                            </div>
                        </div>
                    </div>

                    <button class="ui-btn-primary" type="submit">Confirm booking</button>
                </form>
            </div>
        @elseif($selectedDoctorId !== '' && ($effectiveSpecialtyId ?? '') !== '' && ! $hasAnySlot)
            <div class="ui-card p-6">
                <h3 class="ui-card-title">No available hours</h3>
                <p class="mt-1 text-sm text-slate-600">This doctor has no open slots in the schedule yet, or all upcoming times are booked. Try another doctor or check back later.</p>
            </div>
        @elseif($selectedDoctorId !== '' && ($effectiveSpecialtyId ?? '') === '')
            <div class="ui-card p-6">
                <h3 class="ui-card-title">No specialty for booking</h3>
                <p class="mt-1 text-sm text-slate-600">This doctor has no linked specialty. Please contact the clinic.</p>
            </div>
        @endif
    </div>

    <div id="booking-toast-host" class="pointer-events-none fixed inset-x-0 top-0 z-[100] flex justify-center p-4 sm:p-6" aria-live="polite" aria-atomic="true"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let bookingToastTimer = null;
            const hideBookingNotice = () => {
                const host = document.getElementById('booking-toast-host');
                if (!host) return;
                if (bookingToastTimer) {
                    clearTimeout(bookingToastTimer);
                    bookingToastTimer = null;
                }
                host.innerHTML = '';
            };
            const showBookingNotice = (message, variant = 'warning') => {
                const host = document.getElementById('booking-toast-host');
                if (!host) return;
                hideBookingNotice();

                const panel = document.createElement('div');
                panel.className =
                    'pointer-events-auto flex w-full max-w-md translate-y-2 items-start gap-3 rounded-2xl border px-4 py-3.5 text-sm opacity-0 shadow-card backdrop-blur-md transition-all duration-300 ease-out ' +
                    (variant === 'error'
                        ? 'border-rose-200/90 bg-gradient-to-br from-rose-50/95 via-white to-white text-rose-900 ring-1 ring-rose-100/80'
                        : 'border-cyan-200/90 bg-gradient-to-br from-cyan-50/90 via-white to-white text-cyan-950 ring-1 ring-cyan-100/80');
                panel.setAttribute('role', 'alert');

                const icon = document.createElement('div');
                icon.className =
                    'flex h-10 w-10 shrink-0 items-center justify-center rounded-xl ' +
                    (variant === 'error' ? 'bg-rose-100 text-rose-700' : 'bg-cyan-100 text-cyan-800');
                icon.setAttribute('aria-hidden', 'true');
                icon.innerHTML =
                    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5"><path fill-rule="evenodd" d="M9.401 3.003c1.155-2 4.043-2 5.197 0l7.355 12.748c1.154 2-.577 4.5-2.598 4.5H4.645c-2.022 0-3.752-2.5-2.598-4.5L9.4 3.003zM12 8.25a.75.75 0 01.75.75v3.75a.75.75 0 01-1.5 0V9a.75.75 0 01.75-.75zm0 8.25a.75.75 0 100-1.5.75.75 0 000 1.5z" clip-rule="evenodd" /></svg>';

                const text = document.createElement('p');
                text.className = 'min-w-0 flex-1 pt-1 font-medium leading-snug text-slate-800';
                text.textContent = message;

                const close = document.createElement('button');
                close.type = 'button';
                close.className =
                    'shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100/90 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-cyan-500/40';
                close.setAttribute('aria-label', 'Dismiss');
                close.textContent = '×';
                close.addEventListener('click', hideBookingNotice);

                panel.appendChild(icon);
                panel.appendChild(text);
                panel.appendChild(close);
                host.appendChild(panel);
                requestAnimationFrame(() => {
                    panel.classList.remove('translate-y-2', 'opacity-0');
                });

                bookingToastTimer = setTimeout(hideBookingNotice, 6000);
            };

            const paymentMethod = document.getElementById('payment_method');
            const cardWrapper = document.getElementById('card-fields-wrapper');
            const cardFields = document.querySelectorAll('.card-field');
            const filterSpecialtyId = document.getElementById('filter_specialty_id');
            const specialtyChips = document.querySelectorAll('.specialty-chip');
            const hourButtons = document.querySelectorAll('.hour-slot');
            const datePills = document.querySelectorAll('.date-pill');
            const dayPanels = document.querySelectorAll('.day-slot-panel');
            const activeDayHeading = document.getElementById('active-day-heading');
            const headingTextEl = activeDayHeading?.querySelector('[data-heading-text]');
            const selectedSlotsInputs = document.getElementById('selected-slots-inputs');
            const bookingForm = document.getElementById('booking-form');
            const appointmentDateHidden = document.getElementById('appointment_date_booking');
            const selectedSlots = new Set();

            const slotKey = (date, time) => `${date}|${time}`;
            const parseSlotKey = (key) => {
                const pipe = key.indexOf('|');
                return { date: key.slice(0, pipe), time: key.slice(pipe + 1) };
            };

            specialtyChips.forEach((chip) => {
                chip.addEventListener('click', () => {
                    if (!filterSpecialtyId) return;
                    filterSpecialtyId.value = chip.dataset.specialtyId ?? '';
                    filterSpecialtyId.form?.submit();
                });
            });

            const filterForm = document.getElementById('booking-filter-form');
            document.querySelectorAll('a.doctor-pick-link').forEach((link) => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const url = new URL(link.href);
                    if (filterForm) {
                        const specEl = filterForm.querySelector('input[name="specialty_id"]');
                        if (specEl?.value) {
                            url.searchParams.set('specialty_id', specEl.value);
                        } else {
                            url.searchParams.delete('specialty_id');
                        }
                        const dateEl = filterForm.querySelector('input[name="appointment_date"]');
                        if (dateEl?.value) {
                            url.searchParams.set('appointment_date', dateEl.value);
                        } else {
                            url.searchParams.delete('appointment_date');
                        }
                        const searchEl = filterForm.querySelector('input[name="doctor_search"]');
                        if (searchEl?.value?.trim()) {
                            url.searchParams.set('doctor_search', searchEl.value.trim());
                        } else {
                            url.searchParams.delete('doctor_search');
                        }
                    }
                    window.location.href = url.toString();
                });
            });

            const toggleCardFields = () => {
                if (!paymentMethod || !cardWrapper) return;
                const isCard = paymentMethod.value === 'card';

                cardWrapper.classList.toggle('hidden', !isCard);
                cardFields.forEach((field) => {
                    field.required = isCard;
                    if (!isCard && field.tagName !== 'SELECT') {
                        field.value = '';
                    }
                });
            };

            paymentMethod?.addEventListener('change', toggleCardFields);
            toggleCardFields();

            const syncAppointmentDate = () => {
                if (!appointmentDateHidden) return;
                if (selectedSlots.size === 0) {
                    appointmentDateHidden.value = '';
                    return;
                }
                const firstKey = Array.from(selectedSlots)[0];
                appointmentDateHidden.value = parseSlotKey(firstKey).date;
            };

            const renderSelectedInputs = () => {
                if (!selectedSlotsInputs) return;
                selectedSlotsInputs.innerHTML = '';
                const ordered = Array.from(selectedSlots)
                    .map((k) => parseSlotKey(k))
                    .sort((a, b) => a.time.localeCompare(b.time));
                ordered.forEach(({ time }) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_slots[]';
                    input.value = time;
                    selectedSlotsInputs.appendChild(input);
                });
            };

            const refreshButtons = () => {
                hourButtons.forEach((btn) => {
                    const key = slotKey(btn.dataset.workDate, btn.dataset.slot);
                    const active = selectedSlots.has(key);
                    btn.classList.toggle('bg-gradient-to-r', active);
                    btn.classList.toggle('from-cyan-600', active);
                    btn.classList.toggle('to-teal-600', active);
                    btn.classList.toggle('text-white', active);
                    btn.classList.toggle('border-cyan-600', active);
                });
            };

            const pillActiveClasses = ['border-cyan-600', 'bg-gradient-to-br', 'from-cyan-600', 'to-teal-600', 'text-white', 'shadow-md', 'ring-2', 'ring-cyan-500/40'];
            const pillInactiveClasses = ['border-slate-200', 'bg-white', 'text-slate-800', 'shadow-sm', 'hover:border-cyan-300', 'hover:bg-cyan-50/90'];

            const paintDatePills = (activeWorkDate) => {
                datePills.forEach((pill) => {
                    const on = pill.dataset.workDate === activeWorkDate;
                    pill.setAttribute('aria-selected', on ? 'true' : 'false');
                    pillActiveClasses.forEach((c) => pill.classList.toggle(c, on));
                    pillInactiveClasses.forEach((c) => pill.classList.toggle(c, !on));
                    const badge = pill.querySelector('.pill-count');
                    if (badge) {
                        badge.classList.toggle('bg-white/20', on);
                        badge.classList.toggle('text-white', on);
                        badge.classList.toggle('bg-slate-100', !on);
                        badge.classList.toggle('text-slate-600', !on);
                    }
                });
            };

            const activateDay = (workDate) => {
                dayPanels.forEach((p) => {
                    p.classList.toggle('hidden', p.dataset.workDate !== workDate);
                });
                paintDatePills(workDate);
                const panel = Array.from(dayPanels).find((p) => p.dataset.workDate === workDate);
                if (headingTextEl && panel?.dataset.heading) {
                    headingTextEl.textContent = panel.dataset.heading;
                }
                refreshButtons();
            };

            datePills.forEach((pill) => {
                pill.addEventListener('click', () => {
                    const d = pill.dataset.workDate;
                    selectedSlots.clear();
                    syncAppointmentDate();
                    renderSelectedInputs();
                    activateDay(d);
                    pill.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
                });
            });

            hourButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    const slot = btn.dataset.slot;
                    const workDate = btn.dataset.workDate;
                    const key = slotKey(workDate, slot);

                    if (selectedSlots.has(key)) {
                        selectedSlots.delete(key);
                    } else {
                        selectedSlots.clear();
                        selectedSlots.add(key);
                    }

                    syncAppointmentDate();
                    renderSelectedInputs();
                    refreshButtons();
                });
            });

            bookingForm?.addEventListener('submit', (e) => {
                if (selectedSlots.size < 1) {
                    e.preventDefault();
                    showBookingNotice('Select at least one hour on the grid before confirming your booking.', 'warning');
                    return;
                }
                if (appointmentDateHidden && !appointmentDateHidden.value) {
                    e.preventDefault();
                    showBookingNotice('Pick a valid time slot so we know which day to book.', 'warning');
                }
            });
        });
    </script>
@endsection
