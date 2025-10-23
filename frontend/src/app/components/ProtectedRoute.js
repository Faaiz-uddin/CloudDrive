'use client';

import { useContext, useEffect, useState } from 'react';
import { AuthContext } from '../context/AuthContext';
import { useRouter } from 'next/navigation';

export default function ProtectedRoute({ children, role }) {
  const { user } = useContext(AuthContext);
  const router = useRouter();
  const [isLoaded, setIsLoaded] = useState(false);

  useEffect(() => {
    setIsLoaded(true);
    if (!user) router.push('/auth/login');
    else if (role && user.role !== role) router.push('/dashboard');
  }, [user]);

  if (!isLoaded || !user) return null;
  if (role && user.role !== role) return null;

  return children;
}