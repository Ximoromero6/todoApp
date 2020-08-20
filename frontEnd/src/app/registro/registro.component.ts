import { Component, OnInit, Input } from '@angular/core';
import { FormGroup, FormBuilder, Validators } from '@angular/forms';
import { RegistroService } from './registro.service';
import { Router } from '@angular/router';

@Component({
  selector: 'app-registro',
  templateUrl: './registro.component.html',
  styleUrls: ['./registro.component.scss'],
})
export class RegistroComponent implements OnInit {

  //Notificaciones variables
  statusNotificacion: string;
  iconoNotificacion: string;
  mensajeNotificacion: string;
  mostrarNotificacion: boolean = true;

  formularioRegistro: FormGroup;
  showLoader: boolean = true;

  constructor(
    private formBuilder: FormBuilder,
    private registro: RegistroService,
    private ruta: Router
  ) { }

  ngOnInit(): void {
    this.formularioRegistro = this.formBuilder.group({
      nombre: [''],
      usuario: [
        '',
        Validators.compose([Validators.minLength(4), Validators.required]),
      ],
      email: ['', Validators.compose([Validators.email, Validators.required])],
      clave: [
        '',
        Validators.compose([Validators.minLength(6), Validators.required]),
      ],
    });

    this.focusField();
  }

  focusField() {
    let field1: any = document.querySelector('.field1');
    let field2: any = document.querySelector('.field2');
    let field3: any = document.querySelector('.field3');
    let field4: any = document.querySelector('.field4');

    field1.addEventListener('focus', () => {
      document.getElementById('formField1').classList.add('focused');
    });
    field1.addEventListener('focusout', () => {
      document.getElementById('formField1').classList.remove('focused');
    });

    field2.addEventListener('focus', () => {
      document.getElementById('formField2').classList.add('focused');
    });
    field2.addEventListener('focusout', () => {
      document.getElementById('formField2').classList.remove('focused');
    });

    field3.addEventListener('focus', () => {
      document.getElementById('formField3').classList.add('focused');
    });
    field3.addEventListener('focusout', () => {
      document.getElementById('formField3').classList.remove('focused');
    });

    field4.addEventListener('focus', () => {
      document.getElementById('formField4').classList.add('focused');
    });
    field4.addEventListener('focusout', () => {
      document.getElementById('formField4').classList.remove('focused');
    });
  }

  validarDatosRegistro() {

    if (this.formularioRegistro.invalid) {
      this.formularioRegistro.get('nombre').markAsDirty();
      this.formularioRegistro.get('usuario').markAsDirty();
      this.formularioRegistro.get('email').markAsDirty();
      this.formularioRegistro.get('clave').markAsDirty();
      return;
    }

    this.showLoader = false;

    this.registro
      .registrarUsuario(
        this.formularioRegistro.value.nombre,
        this.formularioRegistro.value.usuario,
        this.formularioRegistro.value.email,
        this.formularioRegistro.value.clave
      )
      .subscribe(
        (response) => {
          this.showLoader = true;
          if (response.status) {
            //Redireccionar a página verificar cuenta pero si hay un error se muestra
            localStorage.setItem('userToken', response.data.token);
            this.ruta.navigateByUrl('/registroFinal', { state: { email: this.formularioRegistro.value.email } });
            setTimeout(() => {

            }, 4000);
          } else {
            this.statusNotificacion = 'error';
            this.iconoNotificacion = 'fas fa-exclamation-circle';
            this.mensajeNotificacion = response.response;
            this.ocultarNotificacion(false);
          }

          setTimeout(() => {
            this.ocultarNotificacion(true);
          }, 5000);
        },
        (error) => {
          this.showLoader = true;
          console.log(error);
        }
      );
  }

  ocultarNotificacion(value: boolean) {
    this.mostrarNotificacion = value;
  }
}
