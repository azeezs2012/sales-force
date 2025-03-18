<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tenants</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}"> <!-- Assuming you have a CSS file -->
</head>
<body>
    <div class="container">
        <h1>Tenants</h1>
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Domain</th>
                    <th>Created At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tenants as $tenant)
                    <tr>
                        <td>{{ $tenant->id }}</td>
                        <td>{{ $tenant->name }}</td>
                        <td>{{ $tenant->domain }}</td>
                        <td>{{ $tenant->created_at }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</body>
</html>