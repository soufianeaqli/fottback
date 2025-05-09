import React from 'react';
import { useNavigate } from 'react-router-dom';

function LoginPrompt() {
    const navigate = useNavigate();

    return (
        <div className="login-prompt">
            <p>Vous devez être connecté pour accéder à cette page.</p>
            <button onClick={() => navigate('/login')} className="btn-login">
                Se connecter
            </button>
        </div>
    );
}

export default LoginPrompt; 