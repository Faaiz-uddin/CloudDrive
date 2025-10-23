'use client';

import { createContext, useState, useEffect } from "react";
import { useRouter } from "next/navigation";
import api from "../lib/axios";

export const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const router = useRouter();
  const [user, setUser] = useState(null);

  // Load user info on mount
  useEffect(() => {
    const u = localStorage.getItem("user");
    if (u) setUser(JSON.parse(u));
  }, []);

  const loginUser = async (email, password, otp = null) => {
    let payload = { email, password };
    if (otp) payload.otp = otp;

    const res = await api.post("/login", payload);
    if (res.data.user) {
      setUser(res.data.user);
      localStorage.setItem("user", JSON.stringify(res.data.user));
    }
    return res.data;
  };

  const logoutUser = async () => {
    await api.post("/logout"); // Laravel route
    setUser(null);
    localStorage.removeItem("user");
    router.push("/auth/login");
  };

  return (
    <AuthContext.Provider value={{ user, loginUser, logoutUser }}>
      {children}
    </AuthContext.Provider>
  );
};