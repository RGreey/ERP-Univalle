<div class="dropdown-menu dropdown-menu-end redtext" aria-labelledby="navbarDropdown">
    <a class="fw-bold redtext" href="{{ route('logout') }}"
        onclick="event.preventDefault();
        document.getElementById('logout-form').submit();">
        {{ __('Logout') }}
    </a>

    <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
        @csrf
    </form>
</div>
<div class="session-status">
    @if(Auth::check())
        <span class="green-text">✓ Sesión activa</span>
    @else
        <span class="red-text">✗ Sin sesión</span>
    @endif
</div>
</html>