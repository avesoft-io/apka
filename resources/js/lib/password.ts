export type PasswordRule = {
    label: string;
    met: boolean;
};

export type PasswordStrength = {
    label: 'Weak' | 'Good' | 'Strong';
    width: 'w-1/3' | 'w-2/3' | 'w-full';
    barClass: string;
};

export function passwordRules(password: string): PasswordRule[] {
    return [
        { label: 'At least 8 characters', met: password.length >= 8 },
        { label: 'A letter', met: /[A-Za-z]/.test(password) },
        { label: 'A number', met: /\d/.test(password) },
    ];
}

export function passwordError(password: string): string | null {
    if (
        password.length === 0 ||
        passwordRules(password).every((rule) => rule.met)
    ) {
        return null;
    }

    return 'Use at least 8 characters, including a letter and a number.';
}

export function passwordStrength(password: string): PasswordStrength | null {
    if (password.length === 0) {
        return null;
    }

    const met = passwordRules(password).filter((rule) => rule.met).length;

    if (met < 3) {
        return {
            label: 'Weak',
            width: 'w-1/3',
            barClass: 'bg-red-700 dark:bg-red-400',
        };
    }

    const mixedCase = /[a-z]/.test(password) && /[A-Z]/.test(password);
    const symbol = /[^A-Za-z0-9]/.test(password);

    if (password.length >= 12 && (mixedCase || symbol)) {
        return {
            label: 'Strong',
            width: 'w-full',
            barClass: 'bg-[#0f3d3e] dark:bg-[#7daba8]',
        };
    }

    return {
        label: 'Good',
        width: 'w-2/3',
        barClass: 'bg-[#146162] dark:bg-[#5f9693]',
    };
}
