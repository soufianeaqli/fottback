import React, { useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './LoginPrompt.css';

function LoginPrompt() {
    const navigate = useNavigate();

    useEffect(() => {
        // Set document title
        document.title = 'Connexion Requise | GoalTime';
    }, []);

    return (
        <div className="login-prompt-container">
            <div className="login-prompt">
                <h2 className="login-prompt-title">Accès Restreint</h2>
                <p>Vous devez être connecté pour accéder à cette page.</p>
                <div className="login-prompt-actions">
                    <button onClick={() => navigate('/login')} className="btn-login">
                        <i className="fas fa-lock-open"></i> Se connecter
                    </button>
                    <button onClick={() => navigate('/')} className="btn-back">
                        Retour à l'accueil
                    </button>
                </div>
            </div>
        </div>
    );
}

export default LoginPrompt; 