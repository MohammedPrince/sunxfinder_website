@extends('admin.layouts.app')

@php
    $isEdit = $customer->exists;
    $pageTitle = $isEdit ? 'Edit Customer' : 'Add New';
@endphp

@section('title', $pageTitle)
@section('breadcrumb', 'Customers / ' . $pageTitle)
@section('eyebrow', 'CUSTOMER MANAGEMENT')
@section('page-title', $pageTitle)

@section('page-description')
    {{ $isEdit
        ? 'Update customer information and manage account status.'
        : 'Create a new customer account on SunXFinder.' }}
@endsection

@php
    $cities = ['Khartoum', 'Port Sudan', 'Atbara', 'Wad Madani', 'Merowe', 'Kassala', 'Al Qadarif', 'Dongola', 'Halfa'];
@endphp

@section('page-actions')
    <a href="{{ route('admin.customers.index') }}" class="btn btn-primary">
        ← Back to Customers
    </a>
@endsection

@section('content')

    {{-- Error message --}}
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible">
            <strong>Error!</strong> {{ session('error') }}
            <button type="button" class="alert-close" onclick="this.parentElement.remove()" aria-label="Close">&times;</button>
        </div>
    @endif

    {{-- Validation errors --}}
    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Please correct the following errors:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel">
        <div class="panel-heading">
            <h2>{{ $isEdit ? 'Customer Information' : 'New Customer Information' }}</h2>
            <p>
                {{ $isEdit ? 'Edit the customer details below.' : 'Enter the customer details to create an account.' }}
            </p>
        </div>

        <form action="{{ $isEdit ? route('admin.customers.update', $customer->id) : route('admin.customers.store') }}"
            method="POST" class="admin-form">
            @csrf

            @if ($isEdit)
                @method('PUT')
            @endif

            <div class="form-grid">

                {{-- Username --}}
                <div class="form-group">
                    <label for="username">Username <span><b style="color: red">*</b></span></label>
                    <input type="text" id="username" name="username" class="form-control"
                        placeholder="Enter customer username" value="{{ old('username', $customer->username) }}"
                        maxlength="191" autocomplete="username" required>
                    @error('username')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Full name --}}
                <div class="form-group">
                    <label for="full_name">Full Name <span><b style="color: red">*</b></span></label>
                    <input type="text" id="full_name" name="full_name" class="form-control"
                        placeholder="Enter customer full name" value="{{ old('full_name', $customer->full_name) }}"
                        maxlength="200" autocomplete="name" required>
                    @error('full_name')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Phone --}}
                <div class="form-group">
                    <label for="phone">Phone Number <span><b style="color: red">*</b></span></label>
                    <input type="tel" id="phone" name="phone" class="form-control" required
                        placeholder="Enter customer phone" value="{{ old('phone', $customer->phone) }}" maxlength="30"
                        autocomplete="tel">
                    @error('phone')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Email --}}
                <div class="form-group">
                    <label for="email">Email Address <span><b style="color: red">*</b></span></label>
                    <input type="email" id="email" name="email" class="form-control"
                        placeholder="Enter customer Email" value="{{ old('email', $customer->email) }}" maxlength="191"
                        autocomplete="email" required>
                    @error('email')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Gender --}}
                <div class="form-group">
                    <label for="gender">Gender <span><b style="color: red">*</b></span></label>
                    <select id="gender" name="gender" class="form-control" required>
                        <option value="">Select gender</option>
                        <option value="male" {{ old('gender', $customer->gender) === 'male' ? 'selected' : '' }}>
                            Male
                        </option>
                        <option value="female" {{ old('gender', $customer->gender) === 'female' ? 'selected' : '' }}>
                            Female
                        </option>
                    </select>
                    @error('gender')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- City --}}
                <div class="form-group">
                    <label for="city">
                        City <span style="color: red;">*</span>
                    </label>

                    <select id="city" name="city" class="form-control" required>
                        <option value="">Select customer city</option>

                        @foreach ($cities as $city)
                            <option value="{{ $city }}"
                                {{ old('city', $customer->city) === $city ? 'selected' : '' }}>
                                {{ $city }}
                            </option>
                        @endforeach
                    </select>

                    @error('city')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Address --}}
                <div class="form-group form-group-full">
                    <label for="address">Address <span><b style="color: red">*</b></span></label>
                    <textarea id="address" name="address" class="form-control" rows="3" maxlength="5000" required
                        placeholder="Enter customer address">{{ old('address', $customer->address) }}</textarea>
                    @error('address')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Status --}}
                <div class="form-group">
                    <label for="status">Account Status <span><b style="color: red">*</b></span></label>
                    <select id="status" name="status" class="form-control" required>
                        @php
                            $currentStatus = old('status', $customer->status ?? 'pending');
                        @endphp

                        <option value="active" {{ $currentStatus === 'active' ? 'selected' : '' }}>
                            Active
                        </option>

                        <option value="pending" {{ $currentStatus === 'pending' ? 'selected' : '' }}>
                            Pending
                        </option>

                        <option value="suspended" {{ $currentStatus === 'suspended' ? 'selected' : '' }}>
                            Suspended
                        </option>

                        <option value="deleted" {{ $currentStatus === 'deleted' ? 'selected' : '' }}>
                            Deleted
                        </option>
                    </select>
                    @error('status')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror

                    <small class="form-hint">
                        Only customers with Active status will have account access enabled.
                    </small>
                </div>

                {{-- Password --}}
                <div class="form-group">
                    <label for="password">
                        Password
                        @unless ($isEdit)
                            <span><b style="color: red">*</b></span>
                        @endunless
                    </label>
                    <input type="password" id="password" name="password" class="form-control" minlength="8"
                        placeholder="Enter customer password" autocomplete="new-password" {{ $isEdit ? '' : 'required' }}
                        placeholder="{{ $isEdit ? 'Leave blank to keep current password' : 'Enter a password (minimum 8 characters)' }}">
                    @error('password')
                        <small class="text-danger">{{ $message }}</small>
                    @enderror
                </div>

                {{-- Confirm password --}}
                <div class="form-group">
                    <label for="password_confirmation">
                        Confirm Password
                        @unless ($isEdit)
                            <span><b style="color: red">*</b></span>
                        @endunless
                    </label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="form-control"
                        placeholder="Enter customer confirmation password" minlength="8" autocomplete="new-password"
                        {{ $isEdit ? '' : 'required' }} placeholder="Confirm the password">
                </div>

            </div>

            <div class="form-actions">
                <a href="{{ route('admin.customers.index') }}" class="btn btn-light">
                    Cancel
                </a>

                <button type="submit" class="btn btn-primary">
                    {{ $isEdit ? 'Save Changes' : 'Create Customer' }}
                </button>
            </div>
        </form>
    </section>

@endsection
