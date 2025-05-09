import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';

function Login({ setUser }) {
    const [isLogin, setIsLogin] = useState(true);
    const [username, setUsername] = useState('');
    const [password, setPassword] = useState('');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [error, setError] = useState('');

    // Modified initialization to ensure default users are always available
    const [users, setUsers] = useState(() => {
        const defaultUsers = [
            { username: 'user', password: 'user', role: 'user' },
            { username: 'admin', password: 'admin', role: 'admin' }
        ];

        try {
            const savedUsers = localStorage.getItem('users');
            const parsedUsers = savedUsers ? JSON.parse(savedUsers) : [];

            // Ensure admin and soufiane users always exist
            const adminExists = parsedUsers.some(u => u.username === 'admin');
            const soufianeExists = parsedUsers.some(u => u.username === 'soufiane');

            if (!adminExists) {
                parsedUsers.push({ username: 'admin', password: 'admin', role: 'admin' });
            }

            if (!soufianeExists) {
                parsedUsers.push({ username: 'soufiane', password: 'soufiane', role: 'user' });
            }

            return parsedUsers.length > 0 ? parsedUsers : defaultUsers;
        } catch (error) {
            console.error("Error loading users from localStorage:", error);
            return defaultUsers;
        }
    });

    // Debug helper - log users to console on component mount
    useEffect(() => {
        console.log("Current users:", users);
    }, []);

    const navigate = useNavigate();

    useEffect(() => {
        if (users.length > 0) {
            localStorage.setItem('users', JSON.stringify(users));
        }
    }, [users]);

    const handleSubmit = (e) => {
        e.preventDefault();
        console.log("Login attempt:", username, password); // Debug log

        if (isLogin) {
            // Case-insensitive username comparison but case-sensitive password
            const user = users.find(
                u => u.username.toLowerCase() === username.toLowerCase() && u.password === password
            );

            if (user) {
                const userData = { username: user.username, role: user.role };
                setUser(userData);
                localStorage.setItem('user', JSON.stringify(userData));
                setError('');
                navigate('/accueil');
            } else {
                setError('Nom d\'utilisateur ou mot de passe incorrect.');
            }
        } else {
            if (users.some(u => u.username.toLowerCase() === username.toLowerCase())) {
                setError('Ce nom d\'utilisateur existe déjà.');
                return;
            }

            if (password.length < 6) {
                setError('Le mot de passe doit contenir au moins 6 caractères.');
                return;
            }

            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                setError('Veuillez entrer une adresse email valide.');
                return;
            }

            const phoneRegex = /^[0-9]{10}$/;
            if (!phoneRegex.test(phone)) {
                setError('Veuillez entrer un numéro de téléphone valide (10 chiffres).');
                return;
            }

            const newUser = {
                username,
                password,
                email,
                phone,
                role: 'user' // Ensure new users have a role assigned
            };
            setUsers([...users, newUser]);

            setUser({ username, role: 'user' });  // Add role to user data
            localStorage.setItem('user', JSON.stringify({ username, role: 'user' }));
            setError('Compte créé avec succès !');

            setTimeout(() => {
                setIsLogin(true);
                setError('');
                navigate('/accueil');
            }, 2000);
        }
    };

    // Function to reset localStorage users (can be added as a dev button)
    const resetUsers = () => {
        const defaultUsers = [
            { username: 'soufiane', password: 'soufiane', role: 'user' },
            { username: 'admin', password: 'admin', role: 'admin' }
        ];
        localStorage.setItem('users', JSON.stringify(defaultUsers));
        setUsers(defaultUsers);
        setError('Utilisateurs réinitialisés');
        setTimeout(() => setError(''), 2000);
    };

    return (
        <div className="login-wrapper">
            <div className="login-container">
                <h2>{isLogin ? 'Connexion' : 'Inscription'}</h2>
                {error && <p className={error.includes('succès') || error.includes('réinitialisés') ? 'success-message' : 'error'}>{error}</p>}
                <form onSubmit={handleSubmit}>
                    <div>
                        <label><i className="fas fa-user"></i> Nom d'utilisateur</label>
                        <input
                            type="text"
                            value={username}
                            onChange={(e) => setUsername(e.target.value)}
                            required
                            placeholder="Entrez votre nom d'utilisateur"
                        />
                    </div>

                    {!isLogin && (
                        <>
                            <div>
                                <label><i className="fas fa-envelope"></i> Email</label>
                                <input
                                    type="email"
                                    value={email}
                                    onChange={(e) => setEmail(e.target.value)}
                                    required
                                    placeholder="Entrez votre email"
                                />
                            </div>
                            <div>
                                <label><i className="fas fa-phone"></i> Numéro de téléphone</label>
                                <input
                                    type="tel"
                                    value={phone}
                                    onChange={(e) => setPhone(e.target.value)}
                                    required
                                    placeholder="Entrez votre numéro de téléphone"
                                />
                            </div>
                        </>
                    )}

                    <div>
                        <label><i className="fas fa-lock"></i> Mot de passe</label>
                        <input
                            type="password"
                            value={password}
                            onChange={(e) => setPassword(e.target.value)}
                            required
                            placeholder="Entrez votre mot de passe"
                        />
                    </div>

                    <button type="submit">
                        {isLogin ? (
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
                                setUsername('');
                                setPassword('');
                                setEmail('');
                                setPhone('');
                            }}
                            className="auth-link"
                        >
                            {isLogin ? " Inscrivez-vous" : " Connectez-vous"}
                        </span>
                    </p>
                </div>

                {/* Dev tool - can be removed in production */}
                <div style={{ marginTop: '20px', textAlign: 'center' }}>
                    <button
                        onClick={resetUsers}
                        style={{
                            background: 'none',
                            border: '1px solid #666',
                            padding: '5px 10px',
                            fontSize: '12px',
                            cursor: 'pointer',
                            color: '#666'
                        }}
                    >
                        Réinitialiser les utilisateurs
                    </button>
                </div>
            </div>
        </div>
    );
}

export default Login;