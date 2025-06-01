import React, { useState, useEffect } from 'react';
import * as contactService from '../services/contactService';
import LoginPrompt from './LoginPrompt';
import './contact.css';

function Contact({ user }) {
    const [formData, setFormData] = useState({
        name: '',
        email: '',
        subject: '',
        message: ''
    });
    const [confirmationMessage, setConfirmationMessage] = useState('');
    const [contactMessages, setContactMessages] = useState([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState(null);
    
    // Charger les messages depuis l'API au chargement du composant
    useEffect(() => {
        if (user && user.role === 'admin') {
            fetchMessages();
        }
    }, [user]);

    const fetchMessages = async () => {
        try {
            setLoading(true);
            const messages = await contactService.getContactMessages();
            setContactMessages(messages);
            setError(null);
        } catch (error) {
            console.error('Erreur lors du chargement des messages:', error);
            setError('Impossible de charger les messages. Veuillez réessayer plus tard.');
        } finally {
            setLoading(false);
        }
    };

    const handleChange = (e) => {
        const { name, value } = e.target;
        setFormData(prevState => ({
            ...prevState,
            [name]: value
        }));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        
        try {
            setLoading(true);
            
            // Envoyer le message via l'API
            await contactService.sendContactMessage(formData);
            
            // Afficher le message de confirmation
            setConfirmationMessage('Votre message a été envoyé avec succès !');
            
            // Réinitialiser le formulaire
            setFormData({
                name: '',
                email: '',
                subject: '',
                message: ''
            });
            
            setError(null);
        } catch (error) {
            console.error('Erreur lors de l\'envoi du message:', error);
            setError('Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.');
            setConfirmationMessage('');
        } finally {
            setLoading(false);
            
            // Masquer le message après 5 secondes
            setTimeout(() => {
                setConfirmationMessage('');
                setError(null);
            }, 5000);
        }
    };
    
    const markAsRead = async (id) => {
        try {
            setLoading(true);
            await contactService.markContactMessageAsRead(id);
            
            // Mettre à jour la liste des messages
            setContactMessages(contactMessages.map(msg => 
                msg.id === id ? { ...msg, read: true } : msg
            ));
            
            setError(null);
        } catch (error) {
            console.error('Erreur lors du marquage du message comme lu:', error);
            setError('Impossible de marquer le message comme lu. Veuillez réessayer.');
        } finally {
            setLoading(false);
        }
    };
    
    const deleteMessage = async (id) => {
        try {
            setLoading(true);
            await contactService.deleteContactMessage(id);
            
            // Mettre à jour la liste des messages
            setContactMessages(contactMessages.filter(msg => msg.id !== id));
            
            setConfirmationMessage('Message supprimé avec succès');
            setError(null);
            
            setTimeout(() => {
                setConfirmationMessage('');
            }, 3000);
        } catch (error) {
            console.error('Erreur lors de la suppression du message:', error);
            setError('Impossible de supprimer le message. Veuillez réessayer.');
            
            setTimeout(() => {
                setError(null);
            }, 3000);
        } finally {
            setLoading(false);
        }
    };

    // Si l'utilisateur n'est pas connecté, afficher le message d'authentification requise
    if (!user) {
        return <LoginPrompt />;
    }

    return (
        <div className="contact-container">
            {user && user.role === 'admin' ? (
                <div className="admin-messages">
                    <h1>Messages reçus</h1>
                    
                    {error && <div className="error-message">{error}</div>}
                    {confirmationMessage && <div className="confirmation-message">{confirmationMessage}</div>}
                    
                    {loading ? (
                        <div className="loading-spinner">
                            <i className="fas fa-spinner fa-pulse"></i>
                            <p>Chargement des messages...</p>
                        </div>
                    ) : contactMessages.length === 0 ? (
                        <div className="no-messages">
                            <i className="fas fa-inbox"></i>
                            <p>Aucun message reçu pour le moment.</p>
                        </div>
                    ) : (
                        <div className="messages-list">
                            {contactMessages.map(msg => (
                                <div key={msg.id} className={`message-card ${!msg.read ? 'unread' : ''}`}>
                                    <div className="message-header">
                                        <h3>{msg.subject}</h3>
                                        <div className="message-meta">
                                            <span className="message-date">{new Date(msg.created_at).toLocaleString()}</span>
                                            {!msg.read && <span className="new-badge">Nouveau</span>}
                                        </div>
                                    </div>
                                    <div className="sender-info">
                                        <div><strong>De:</strong> {msg.name}</div>
                                        <div><strong>Email:</strong> {msg.email}</div>
                                    </div>
                                    <div className="message-content">{msg.message}</div>
                                    <div className="message-actions">
                                        {!msg.read && (
                                            <button 
                                                className="action-btn read-btn" 
                                                onClick={() => markAsRead(msg.id)}
                                                disabled={loading}
                                            >
                                                <i className="fas fa-check"></i> Marquer comme lu
                                            </button>
                                        )}
                                        <button 
                                            className="action-btn delete-btn" 
                                            onClick={() => deleteMessage(msg.id)}
                                            disabled={loading}
                                        >
                                            <i className="fas fa-trash"></i> Supprimer
                                        </button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            ) : (
                <>
                    <div className="contact-info">
                        <h1>Contactez-nous</h1>
                        <p className="contact-subtitle">Nous sommes à votre disposition pour répondre à toutes vos questions</p>
                        
                        <div className="info-section">
                            <div className="info-item">
                                <i className="fas fa-map-marker-alt"></i>
                                <h3>Notre Adresse</h3>
                                <p>Bab Tizimi</p>
                                <p>Meknes, Maroc</p>
                            </div>
                            
                            <div className="info-item">
                                <i className="fas fa-phone"></i>
                                <h3>Téléphone</h3>
                                <div className="info-contact-value">
                                    <a href="tel:+212612345678" className="contact-link">
                                        <i className="fas fa-mobile-alt"></i>
                                        <span>+212 6 12 34 56 78</span>
                                    </a>
                                    <a href="tel:+212522334455" className="contact-link">
                                        <i className="fas fa-phone-alt"></i>
                                        <span>+212 5 22 33 44 55</span>
                                    </a>
                                </div>
                            </div>
                            
                            <div className="info-item">
                                <i className="fas fa-envelope"></i>
                                <h3>Email</h3>
                                <div className="info-contact-value">
                                    <a href="mailto:contact@terrainsport.com" className="contact-link">
                                        <i className="fas fa-envelope"></i>
                                        <span>contact@terrainsport.com</span>
                                    </a>
                                    <a href="mailto:info@terrainsport.com" className="contact-link">
                                        <i className="fas fa-inbox"></i>
                                        <span>info@terrainsport.com</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                        
                        <div className="additional-info">
                            <div className="social-section">
                                <h3 className="social-heading"><i className="fas fa-heart"></i> Suivez-nous</h3>
                                <div className="social-icons-container">
                                    <a href="https://www.facebook.com/profile.php?id=100039464749957" target="_blank" rel="noopener noreferrer" className="social-icon-modern">
                                        <i className="fab fa-facebook-f"></i>
                                        <span>Facebook</span>
                                    </a>
                                    <a href="https://www.instagram.com/soufiane.aqq/" target="_blank" rel="noopener noreferrer" className="social-icon-modern">
                                        <i className="fab fa-instagram"></i>
                                        <span>Instagram</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="contact-form-container">
                        <div className="contact-form">
                            <h2>Envoyez-nous un message</h2>
                            {error && <div className="error-message">{error}</div>}
                            {confirmationMessage && (
                                <div className="confirmation-message">
                                    {confirmationMessage}
                                </div>
                            )}
                            <form onSubmit={handleSubmit}>
                                <div className="form-row">
                                    <div className="form-group">
                                        <label>Nom complet</label>
                                        <input
                                            type="text"
                                            name="name"
                                            value={formData.name}
                                            onChange={handleChange}
                                            required
                                            placeholder="Votre nom"
                                            disabled={loading}
                                        />
                                    </div>
                                    <div className="form-group">
                                        <label>Email</label>
                                        <input
                                            type="email"
                                            name="email"
                                            value={formData.email}
                                            onChange={handleChange}
                                            required
                                            placeholder="Votre email"
                                            disabled={loading}
                                        />
                                    </div>
                                </div>
                                <div className="form-group">
                                    <label>Sujet</label>
                                    <input
                                        type="text"
                                        name="subject"
                                        value={formData.subject}
                                        onChange={handleChange}
                                        required
                                        placeholder="Sujet de votre message"
                                        disabled={loading}
                                    />
                                </div>
                                <div className="form-group">
                                    <label>Message</label>
                                    <textarea
                                        name="message"
                                        value={formData.message}
                                        onChange={handleChange}
                                        required
                                        placeholder="Votre message"
                                        rows="6"
                                        disabled={loading}
                                    ></textarea>
                                </div>
                                <button type="submit" className="submit-btn" disabled={loading}>
                                    {loading ? (
                                        <>
                                            <i className="fas fa-spinner fa-spin"></i> Envoi en cours...
                                        </>
                                    ) : (
                                        <>
                                            <i className="fas fa-paper-plane"></i> Envoyer le message
                                        </>
                                    )}
                                </button>
                            </form>
                        </div>
                        
                        <div className="map-container">
                            <h3>Notre emplacement</h3>
                            <div className="map-iframe">
                                <iframe 
                                    src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3311.788403964403!2d-5.570112485106394!3d33.89331013358611!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xd9f8b321750f0f1%3A0x15146c8f9311c3df!2sBab%20Tizimi%2C%20Mekn%C3%A8s!5e0!3m2!1sfr!2sma!4v1621505845977!5m2!1sfr!2sma" 
                                    width="100%" 
                                    height="300" 
                                    style={{ border: 0 }} 
                                    allowFullScreen="" 
                                    loading="lazy" 
                                    referrerPolicy="no-referrer-when-downgrade"
                                    title="Localisation de terrain à Meknès"
                                    className="google-map"
                                ></iframe>
                            </div>
                        </div>
                    </div>
                </>
            )}
        </div>
    );
}

export default Contact;