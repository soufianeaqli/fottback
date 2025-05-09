import React, { useState, useRef, useEffect } from 'react';
import './Header.css';
import { Link, useNavigate } from 'react-router-dom';

function Header({ user, logout }) {
    const navigate = useNavigate();
    const [isLogoutModalOpen, setIsLogoutModalOpen] = useState(false);
    const [isProfileMenuOpen, setIsProfileMenuOpen] = useState(false);
    const menuRef = useRef(null);

    const handleLogout = () => {
        setIsLogoutModalOpen(false);
        setIsProfileMenuOpen(false);
        logout();
        navigate('/login');
    };

    useEffect(() => {
        function handleClickOutside(event) {
            if (menuRef.current && !menuRef.current.contains(event.target)) {
                setIsProfileMenuOpen(false);
            }
        }

        document.addEventListener('mousedown', handleClickOutside);
        return () => {
            document.removeEventListener('mousedown', handleClickOutside);
        };
    }, []);

    const getInitials = (name) => {
        return name.charAt(0).toUpperCase();
    };

    return (
        <nav className="navbar">
            <div className="logo">
                <Link to="/">LWST</Link>
            </div>

            <div className="nav-links">
                <Link to="/accueil">Accueil</Link>
                <Link to="/terrain">Terrain</Link>
                <Link to="/tournoi">Tournois</Link>
                <Link to="/reservation">Réservation</Link>
                <Link to="/contact">Contact</Link>
            </div>

            <div className="profile-menu" ref={menuRef}>
                {user ? (
                    <>
                        <button 
                            className="profile-button"
                            onClick={() => setIsProfileMenuOpen(!isProfileMenuOpen)}
                        >
                            <div className="profile-avatar">
                                {getInitials(user.username)}
                            </div>
                            <span className="profile-name">{user.username}</span>
                            <i className={`fas fa-chevron-${isProfileMenuOpen ? 'up' : 'down'}`}></i>
                        </button>

                        {isProfileMenuOpen && (
                            <div className="profile-dropdown">
                                <div className="profile-dropdown-header">
                                    <h3>{user.username}</h3>
                                    <p>{user.role === 'admin' ? 'Administrateur' : 'Utilisateur'}</p>
                                </div>
                                
                                <Link to="/parametres" className="profile-dropdown-item">
                                    <i className="fas fa-cog"></i>
                                    Paramètres du compte
                                </Link>
                                
                                <div className="profile-dropdown-divider"></div>
                                
                                <div 
                                    className="profile-dropdown-item"
                                    onClick={() => setIsLogoutModalOpen(true)}
                                >
                                    <i className="fas fa-sign-out-alt"></i>
                                    Se déconnecter
                                </div>
                            </div>
                        )}

                        {isLogoutModalOpen && (
                            <div className="modal">
                                <div className="modal-content">
                                    <h2>Confirmation de Déconnexion</h2>
                                    <p>Êtes-vous sûr de vouloir vous déconnecter ?</p>
                                    <div className="modal-actions">
                                        <button className="btn-ajt" onClick={handleLogout}>Oui</button>
                                        <button className="btn-annuler" onClick={() => setIsLogoutModalOpen(false)}>Non</button>
                                    </div>
                                </div>
                            </div>
                        )}
                    </>
                ) : (
                    <Link to="/login">
                        <button className="login-button">
                            <i className="fas fa-user"></i> Se connecter
                        </button>
                    </Link>
                )}
            </div>
        </nav>
    );
}

export default Header;
