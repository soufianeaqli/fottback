import React, { useState, useEffect } from 'react';
import { useNavigate } from 'react-router-dom';
import './parametres.css';

function Parametres({ user, setUser }) {
    const navigate = useNavigate();
    const [activeTab, setActiveTab] = useState('profile');
    const [formData, setFormData] = useState({
        username: user?.username || 'Utilisateur',
        email: user?.email || 'utilisateur@example.com',
        phone: user?.phone || '0612345678',
        currentPassword: '',
        newPassword: '',
        confirmPassword: ''
    });
    const [notifications, setNotifications] = useState({
        emailNotifications: true,
        tournamentNotifications: false,
        reminderNotifications: true
    });
    const [successMessage, setSuccessMessage] = useState('');
    const [errorMessage, setErrorMessage] = useState('');
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Simuler un chargement
        const timer = setTimeout(() => {
            if (!user) {
                navigate('/login');
                return;
            }
            setIsLoading(false);
        }, 1000);

        return () => clearTimeout(timer);
    }, [user, navigate]);

    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setFormData(prev => ({
            ...prev,
            [name]: value
        }));
        setSuccessMessage('');
        setErrorMessage('');
    };

    const handleNotificationChange = (name) => {
        setNotifications(prev => ({
            ...prev,
            [name]: !prev[name]
        }));
        setSuccessMessage('Préférences de notification mises à jour');
        setTimeout(() => setSuccessMessage(''), 3000);
    };

    const validateEmail = (email) => {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    };

    const validatePhone = (phone) => {
        return /^[0-9]{10}$/.test(phone);
    };

    const handleProfileUpdate = (e) => {
        e.preventDefault();
        
        // Validation
        if (!formData.username.trim()) {
            setErrorMessage("Le nom d'utilisateur est requis");
            return;
        }
        if (!validateEmail(formData.email)) {
            setErrorMessage("L'adresse email n'est pas valide");
            return;
        }
        if (formData.phone && !validatePhone(formData.phone)) {
            setErrorMessage("Le numéro de téléphone n'est pas valide");
            return;
        }

        // Simuler la mise à jour
        setUser(prev => ({
            ...prev,
            username: formData.username,
            email: formData.email,
            phone: formData.phone
        }));
        setSuccessMessage('Profil mis à jour avec succès !');
        setTimeout(() => setSuccessMessage(''), 3000);
    };

    const handlePasswordChange = (e) => {
        e.preventDefault();

        // Validation
        if (!formData.currentPassword) {
            setErrorMessage('Le mot de passe actuel est requis');
            return;
        }
        if (formData.newPassword.length < 8) {
            setErrorMessage('Le nouveau mot de passe doit contenir au moins 8 caractères');
            return;
        }
        if (formData.newPassword !== formData.confirmPassword) {
            setErrorMessage('Les mots de passe ne correspondent pas');
            return;
        }

        // Simuler le changement de mot de passe
        setSuccessMessage('Mot de passe modifié avec succès !');
        setFormData(prev => ({
            ...prev,
            currentPassword: '',
            newPassword: '',
            confirmPassword: ''
        }));
        setTimeout(() => setSuccessMessage(''), 3000);
    };

    if (isLoading) {
        return (
            <div className="parametres-container">
                <div className="loading-spinner">
                    <i className="fas fa-spinner fa-spin"></i>
                    <p>Chargement...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="parametres-container">
            <div className="parametres-header">
                <h1>Paramètres du compte</h1>
                <p>Gérez vos informations personnelles et vos préférences</p>
            </div>

            <div className="parametres-content">
                <div className="parametres-sidebar">
                    <button 
                        className={`sidebar-button ${activeTab === 'profile' ? 'active' : ''}`}
                        onClick={() => setActiveTab('profile')}
                    >
                        <i className="fas fa-user"></i>
                        Profil
                    </button>
                    <button 
                        className={`sidebar-button ${activeTab === 'security' ? 'active' : ''}`}
                        onClick={() => setActiveTab('security')}
                    >
                        <i className="fas fa-lock"></i>
                        Sécurité
                    </button>
                    <button 
                        className={`sidebar-button ${activeTab === 'notifications' ? 'active' : ''}`}
                        onClick={() => setActiveTab('notifications')}
                    >
                        <i className="fas fa-bell"></i>
                        Notifications
                    </button>
                </div>

                <div className="parametres-main">
                    {successMessage && (
                        <div className="success-message">
                            <i className="fas fa-check-circle"></i>
                            {successMessage}
                        </div>
                    )}
                    {errorMessage && (
                        <div className="error-message">
                            <i className="fas fa-exclamation-circle"></i>
                            {errorMessage}
                        </div>
                    )}

                    {activeTab === 'profile' && (
                        <div className="parametres-section">
                            <h2>Informations du profil</h2>
                            <form onSubmit={handleProfileUpdate}>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-user"></i>
                                        Nom d'utilisateur
                                    </label>
                                    <input
                                        type="text"
                                        name="username"
                                        value={formData.username}
                                        onChange={handleInputChange}
                                        placeholder="Votre nom d'utilisateur"
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-envelope"></i>
                                        Email
                                    </label>
                                    <input
                                        type="email"
                                        name="email"
                                        value={formData.email}
                                        onChange={handleInputChange}
                                        placeholder="Votre email"
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-phone"></i>
                                        Téléphone
                                    </label>
                                    <input
                                        type="tel"
                                        name="phone"
                                        value={formData.phone}
                                        onChange={handleInputChange}
                                        placeholder="Votre numéro de téléphone"
                                        pattern="[0-9]{10}"
                                    />
                                </div>
                                <button type="submit" className="btn-save">
                                    <i className="fas fa-save"></i>
                                    Enregistrer les modifications
                                </button>
                            </form>
                        </div>
                    )}

                    {activeTab === 'security' && (
                        <div className="parametres-section">
                            <h2>Sécurité</h2>
                            <form onSubmit={handlePasswordChange}>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-lock"></i>
                                        Mot de passe actuel
                                    </label>
                                    <input
                                        type="password"
                                        name="currentPassword"
                                        value={formData.currentPassword}
                                        onChange={handleInputChange}
                                        placeholder="Entrez votre mot de passe actuel"
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-key"></i>
                                        Nouveau mot de passe
                                    </label>
                                    <input
                                        type="password"
                                        name="newPassword"
                                        value={formData.newPassword}
                                        onChange={handleInputChange}
                                        placeholder="Entrez votre nouveau mot de passe"
                                        minLength="8"
                                        required
                                    />
                                </div>
                                <div className="form-group">
                                    <label>
                                        <i className="fas fa-check-circle"></i>
                                        Confirmer le mot de passe
                                    </label>
                                    <input
                                        type="password"
                                        name="confirmPassword"
                                        value={formData.confirmPassword}
                                        onChange={handleInputChange}
                                        placeholder="Confirmez votre nouveau mot de passe"
                                        minLength="8"
                                        required
                                    />
                                </div>
                                <button type="submit" className="btn-save">
                                    <i className="fas fa-key"></i>
                                    Changer le mot de passe
                                </button>
                            </form>
                        </div>
                    )}

                    {activeTab === 'notifications' && (
                        <div className="parametres-section">
                            <h2>Préférences de notifications</h2>
                            <div className="notification-preferences">
                                <div className="notification-option">
                                    <label className="toggle-switch">
                                        <input
                                            type="checkbox"
                                            checked={notifications.emailNotifications}
                                            onChange={() => handleNotificationChange('emailNotifications')}
                                        />
                                        <span className="toggle-slider"></span>
                                    </label>
                                    <div className="notification-text">
                                        <h3>Notifications par email</h3>
                                        <p>Recevoir des notifications par email pour les réservations</p>
                                    </div>
                                </div>
                                <div className="notification-option">
                                    <label className="toggle-switch">
                                        <input
                                            type="checkbox"
                                            checked={notifications.tournamentNotifications}
                                            onChange={() => handleNotificationChange('tournamentNotifications')}
                                        />
                                        <span className="toggle-slider"></span>
                                    </label>
                                    <div className="notification-text">
                                        <h3>Notifications de tournois</h3>
                                        <p>Être informé des nouveaux tournois</p>
                                    </div>
                                </div>
                                <div className="notification-option">
                                    <label className="toggle-switch">
                                        <input
                                            type="checkbox"
                                            checked={notifications.reminderNotifications}
                                            onChange={() => handleNotificationChange('reminderNotifications')}
                                        />
                                        <span className="toggle-slider"></span>
                                    </label>
                                    <div className="notification-text">
                                        <h3>Rappels</h3>
                                        <p>Recevoir des rappels pour vos réservations</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </div>
    );
}

export default Parametres; 