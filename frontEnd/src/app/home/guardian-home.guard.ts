import { Injectable } from '@angular/core';
import { CanActivate, ActivatedRouteSnapshot, RouterStateSnapshot, Router } from '@angular/router';

@Injectable({
  providedIn: 'root'
})
export class GuardianHomeGuard implements CanActivate {

  constructor(private router: Router) { }

  canActivate() {

    console.log('No estás logueado');
    this.router.navigate(['/']);
    return false;

  }

}
