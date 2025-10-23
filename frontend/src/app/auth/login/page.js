'use client';

import { useState, useContext } from 'react';
import { AuthContext } from '../../../context/AuthContext';
import { useRouter } from 'next/navigation';
import api from '../../../lib/axios';

export default function LoginPage() {
  const { loginUser } = useContext(AuthContext);
  const router = useRouter();
  const [form, setForm] = useState({ email:'', password:'' });
  const [otp, setOtp] = useState('');
  const [requiresOtp, setRequiresOtp] = useState(false);
  const [error, setError] = useState('');

  const handleLogin = async (e) => {
    e.preventDefault();
    try {
      const res = await loginUser(form.email, form.password);
      if (res.requires_otp) setRequiresOtp(true);
      else router.push("/dashboard");
    } catch (err) {
      setError(err.response?.data?.message || "Login failed");
    }
  };

  const handleVerifyOtp = async (e) => {
    e.preventDefault();
    try {
      const res = await loginUser(form.email, form.password, otp);
      router.push("/dashboard");
    } catch (err) {
      setError(err.response?.data?.message || "OTP failed");
    }
  };

  return (
    <div>
      {!requiresOtp ? (
        <form onSubmit={handleLogin}>
          <input placeholder="Email" name="email" onChange={e => setForm({...form, email: e.target.value})}/>
          <input placeholder="Password" name="password" type="password" onChange={e => setForm({...form, password: e.target.value})}/>
          <button type="submit">Login</button>
        </form>
      ) : (
        <form onSubmit={handleVerifyOtp}>
          <input placeholder="Enter OTP" value={otp} onChange={e => setOtp(e.target.value)} />
          <button type="submit">Verify OTP</button>
        </form>
      )}
      {error && <p>{error}</p>}
    </div>
  );
}