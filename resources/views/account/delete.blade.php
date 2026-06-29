<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Delete Account - PuckRecruiter</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Nunito', sans-serif;
        }
        .delete-account-container {
            max-width: 500px;
            margin: 50px auto;
            padding: 30px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .alert {
            margin-bottom: 20px;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 8px;
        }
        .btn-danger {
            background-color: #dc3545;
            border-color: #dc3545;
        }
        .btn-danger:hover {
            background-color: #c82333;
            border-color: #bd2130;
        }
        .warning-box {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .warning-box h5 {
            color: #856404;
            margin-bottom: 10px;
        }
        .warning-box ul {
            margin-bottom: 0;
            padding-left: 20px;
        }
        .warning-box li {
            color: #856404;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="delete-account-container">
            <h2 class="text-center mb-4">Delete Account</h2>
            
            @if(session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <p class="text-muted text-center mb-3">
                Account deletion for <strong>Puck Recruiter</strong> by <strong>Team Pumpkin</strong>.
            </p>

            <div class="warning-box">
                <h5>⚠️ Warning: This action cannot be undone!</h5>
                <p>Deleting your account will permanently remove the following data:</p>
                <ul>
                    <li>Profile information (name, email address, profile photo)</li>
                    <li>Account credentials and authentication identifiers</li>
                    <li>Job applications, saved jobs, and activity history</li>
                    <li>Location data and uploaded documents (e.g. resume / listings)</li>
                    <li>Posts, comments, and interactions</li>
                    <li>Active subscriptions are cancelled</li>
                </ul>
                <p class="mb-1"><strong>Data we retain after deletion:</strong></p>
                <ul>
                    <li>Transaction and billing records — retained for up to 7 years to meet tax and legal obligations</li>
                    <li>Anonymized, aggregated analytics that cannot identify you — retained indefinitely</li>
                    <li>Records we are legally required to keep — retained for the statutory period, then permanently deleted</li>
                </ul>
                <p class="mb-0">Your account is deactivated immediately and remaining data is permanently deleted within 30 days.</p>
            </div>

            <p class="small text-muted">
                Signed in with Google and don't have a password? Email
                <a href="mailto:privacy@teampumpkin.com">privacy@teampumpkin.com</a>
                from your registered email with the subject "Delete my Puck account".
                We action requests within 30 days.
            </p>

            <form method="POST" action="{{ route('account.delete') }}" id="deleteAccountForm">
                @csrf
                
                @if(request('token'))
                    <input type="hidden" name="token" value="{{ request('token') }}">
                @endif

                <div class="mb-3">
                    <label for="password" class="form-label">Enter your password to confirm</label>
                    <input 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        id="password" 
                        name="password" 
                        required 
                        autofocus
                        placeholder="Enter your password"
                    >
                    @error('password')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-danger btn-lg" id="deleteButton">
                        Delete My Account
                    </button>
                    <a href="/" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.getElementById('deleteAccountForm').addEventListener('submit', function(e) {
            const confirmed = confirm('Are you absolutely sure you want to delete your account? This action cannot be undone!');
            if (!confirmed) {
                e.preventDefault();
                return false;
            }
            
            const button = document.getElementById('deleteButton');
            button.disabled = true;
            button.textContent = 'Deleting...';
        });
    </script>
</body>
</html>

