import numpy as np
import matplotlib.pyplot as plt
import control as ctrl

# Dati di esempio (sostituisci con i tuoi dati)
dati = np.array([100, 98, 98, 96, 93, 92, 89, 88, 85, 85, 83, 86, 81, 77, 78, 71, 71, 70, 70, 69,
                 65, 68, 68, 67, 66, 66, 65, 65, 65, 60, 59, 68, 64, 67, 63, 64, 61, 61, 62, 56,
                 58, 58, 63, 58, 58, 57, 57, 61, 56, 60, 57, 56, 52, 51, 56, 55, 55, 52, 54, 54,
                 53, 54, 54, 52, 53, 56, 53, 50, 53, 53, 53, 52, 57, 52, 54, 51, 50, 50, 46, 50,
                 50, 49, 51, 49, 51, 48, 51, 45, 48, 48, 48, 43, 48, 49, 47, 42, 51, 47, 43, 46,
                 46, 46, 46, 41, 50, 46, 46, 44, 48, 45, 40, 44, 49, 39, 44, 38, 37])
x = np.arange(len(dati))

# Supponiamo di avere una funzione di trasferimento già definita (esempio)
num = [1]  # Coefficiente numeratore (modifica come necessario)
den = [1, 1]  # Coefficiente denominatore (modifica come necessario)

sys = ctrl.TransferFunction(num, den)

# Simula la risposta del sistema ai dati di input
t, y_simulata = ctrl.forced_response(sys, T=x, U=dati)

# Plot dei dati reali e della risposta simulata
plt.figure(figsize=(14, 7))
plt.plot(x, dati, label='Dati Originali', marker='o')
plt.plot(t, y_simulata, label='Risposta Simulata', linestyle='--', color='red')

plt.title('Confronto tra Dati Originali e Risposta Simulata della Funzione di Trasferimento')
plt.xlabel('Tempo')
plt.ylabel('Valore')
plt.legend()
plt.grid(True)
plt.show()

# Calcolo della risposta in frequenza per ottenere Ku e Tu
omega, mag, phase = ctrl.frequency_response(sys, omega=np.logspace(-1, 1, 100))

# Stampa delle frequenze, magnitudini e fasi per il debug
print("Omega:", omega)
print("Magnitudine:", mag)
print("Fase:", phase)

# Trova Ku come il massimo valore di magnitudine e Tu come il periodo di oscillazione
Ku = np.max(mag)
Tu = 2 * np.pi / np.mean(omega)

# Calcolo dei parametri PID usando la formula di Ziegler-Nichols
Kp = 0.6 * Ku
Ti = Tu / 2
Td = Tu / 8

print(f"Funzione di trasferimento stimata: {sys}")
print(f"Parametri PID calcolati: Kp = {Kp:.2f}, Ti = {Ti:.2f}, Td = {Td:.2f}")
