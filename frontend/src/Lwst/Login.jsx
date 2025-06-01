import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import * as authService from '../services/authService';
import './login.css';

function Login({ setUser }) {
    const [isLogin, setIsLogin] = useState(true);
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [name, setName] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [error, setError] = useState('');
    const [loading, setLoading] = useState(false);
    const [success, setSuccess] = useState('');
    const [userData, setUserData] = useState(null);

    const navigate = useNavigate();

    // Ajout d'un titre personnalisé pour la page
    useEffect(() => {
        document.title = isLogin ? 'Connexion | GoalTime' : 'Inscription | GoalTime';
        return () => {
            document.title = 'GoalTime';
        };
    }, [isLogin]);

    const handleSubmit = async (e) => {
        e.preventDefault();
        setError('');
        setSuccess('');
        setLoading(true);

        try {
            if (isLogin) {
                // Tentative de connexion
                const data = await authService.login({
                    username,
                    password
                });

                // Mise à jour de l'état utilisateur dans le composant parent
                setUser(data);
                // Stocker également les données pour l'utilisation locale
                setUserData(data);
                
                // Message de succès adapté si c'est un admin
                if (data.role === 'admin') {
                    setSuccess(`Connexion administrateur réussie ! Bienvenue ${data.username}`);
                } else {
                    setSuccess('Connexion réussie !');
                }

                // Redirection après un court délai
                setTimeout(() => {
                    navigate('/accueil');
                }, 2000);
            } else {
                // Vérification du format de l'email
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(email)) {
                    setError('Veuillez entrer une adresse email valide.');
                    setLoading(false);
                    return;
                }

                // Vérification du format du numéro de téléphone
                const phoneRegex = /^[0-9]{10}$/;
                if (!phoneRegex.test(phone)) {
                    setError('Veuillez entrer un numéro de téléphone valide (10 chiffres).');
                    setLoading(false);
                    return;
                }

                // Vérification de la longueur du mot de passe
                if (password.length < 6) {
                    setError('Le mot de passe doit contenir au moins 6 caractères.');
                    setLoading(false);
                    return;
                }

                // Tentative d'inscription
                const data = await authService.register({
                    name,
                    username,
                    email,
                    password,
                    phone
                });

                // Mise à jour de l'état utilisateur dans le composant parent
                setUser(data);
                setUserData(data);
                setSuccess('Compte créé avec succès !');

                // Redirection après un court délai
                setTimeout(() => {
                    navigate('/accueil');
                }, 2000);
            }
        } catch (error) {
            console.error('Erreur:', error);
            setError(error.message || (isLogin ? 'Échec de la connexion' : 'Échec de l\'inscription'));
        } finally {
            setLoading(false);
        }
    };

    // Fonction pour vérifier la disponibilité du nom d'utilisateur
    const checkUsername = async (value) => {
        if (value.length < 3) return;
        
        try {
            const isAvailable = await authService.checkUsernameAvailable(value);
            if (!isAvailable && !isLogin) {
                setError('Ce nom d\'utilisateur existe déjà.');
            } else {
                setError('');
            }
        } catch (error) {
            console.error('Erreur lors de la vérification du nom d\'utilisateur:', error);
        }
    };

    // Vérifier la disponibilité du nom d'utilisateur lorsqu'il change
    useEffect(() => {
        if (!isLogin && username.length >= 3) {
            const timer = setTimeout(() => {
                checkUsername(username);
            }, 500);
            return () => clearTimeout(timer);
        }
    }, [username, isLogin]);

    return (
        <div className="login-wrapper">
            <div className="login-container">
                <h2>{isLogin ? 'Connexion' : 'Inscription'}</h2>
                
                {error && <p className="error">{error}</p>}
                {success && (
                    <p className={`success-message ${userData && userData.role === 'admin' ? 'admin-success-message' : ''}`}>
                        {success}
                    </p>
                )}
                
                <form onSubmit={handleSubmit}>
                    <div>
                        <label><i className="fas fa-user icon-green"></i> Identifiant</label>
                        <input
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            required
                            placeholder="Identifiant"
                            disabled={loading}
                        />
                    </div>

                    {!isLogin && (
                        <>
                            <div>
                                <label><i className="fas fa-user icon-green"></i> Nom complet</label>
                                <input
                                    type="text"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    required
                                    placeholder="Nom et prénom"
                                    disabled={loading}
                                />
                            </div>
                            <div>
                                <label><i className="fas fa-envelope icon-green"></i> Email</label>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    placeholder="Email"
                                    disabled={loading}
                                />
                            </div>
                            <div>
                                <label><i className="fas fa-phone icon-green"></i> Téléphone</label>
                                <input
                                    type="tel"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    required
                                    placeholder="Téléphone"
                                    disabled={loading}
                                />
                            </div>
                        </>
                    )}

                    <div>
                        <label><i className="fas fa-lock icon-green"></i> Mot de passe</label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            placeholder="Mot de passe"
                            disabled={loading}
                        />
                    </div>

                    <button type="submit" disabled={loading}>
                        {loading ? (
                            <><i className="fas fa-spinner fa-spin"></i> {isLogin ? 'Connexion...' : 'Inscription...'}</>
                        ) : isLogin ? (
                            <><i className="fas fa-sign-in-alt"></i> Se connecter</>
                        ) : (
                            <><i className="fas fa-user-plus"></i> S'inscrire</>
                        )}
                    </button>
                </form>

                <div className="auth-switch">
                    <p>
                        {isLogin ? "Vous n'avez pas de compte ?" : "Vous avez déjà un compte ?"}
                        <span
                            onClick={() => {
                                setIsLogin(!isLogin);
                                setError('');
                                setSuccess('');
                                setUsername('');
                                setPassword('');
                                setName('');
                                setEmail('');
                                setPhone('');
                            }}
                            className="auth-link"
                        >
                            {isLogin ? (
                                <> <span className="green-highlight">Inscrivez-vous</span></>
                            ) : (
                                " Connectez-vous"
                            )}
                        </span>
                    </p>
                </div>
            </div>
        </div>
    );
}

export default Login;