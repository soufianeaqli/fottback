
import React from 'react';
import { Link } from 'react-router-dom';
import './lwst.css'

function Accueil({ user }) {
    return (
        <div className='edo'>
            <h1 className='re'>Réservez Votre Terrain de Mini-Foot</h1>
            <p className='ree'>Profitez de nos terrains de qualité pour vos matchs entre amis ou en compétition.
                Réservation simple et rapide, disponibilité en temps réel.</p>
            <Link to={'/terrain'}>
                <button className='btn1'>
                    {user?.role === 'admin' ? 'Get Started' : 'Reserver Maintenant'}
                </button>
            </Link>
        </div>
    );
}

export default Accueil;