import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';
import { LoginComponent } from './login/login.component';

@Injectable({
  providedIn: 'root'
})
export class GuardianHomeGuard implements CanActivate {

  componentLogin: LoginComponent
  constructor(private router: Router) { }

  canActivate() {

    if (!this.componentLogin.autorizadoFunction()) {
      console.log('No estás logueado');
      this.router.navigate(['']);
      return false;
    }
    return true
  }

}
